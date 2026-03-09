<?php

namespace App\Http\Controllers;

use App\Models\CashflowEntry;
use App\Models\Company;
use App\Models\CompanyReportRow;
use App\Models\CompanyReportSectionTitle;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyReportController extends Controller
{
    private function allowedCompanyIds(): array
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        if ($user->all_companies) {
            return Company::query()->pluck('id')->all();
        }
        return $user->companies()->pluck('companies.id')->all();
    }

    /**
     * Show monthly report (4 parts) for a company. Year from query; first row = months.
     */
    public function show(Request $request, int $id): View|RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        if (! in_array($id, $companyIds, true)) {
            return redirect()->route('companies.index')->with('error', __('Not allowed to view this company.'));
        }

        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }

        // 快速切換同一使用者可見的公司
        $companies = Company::query()
            ->whereIn('id', $companyIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'base_currency']);

        $currentYear = (int) date('Y');
        $year = (int) $request->get('year', $currentYear);
        if ($year < 2000 || $year > 2100) {
            $year = $currentYear;
        }

        $years = $this->availableYears($id);
        // Always allow current year + previous year for data entry
        $years[] = $currentYear;
        $years[] = $currentYear - 1;
        $years = array_values(array_unique($years));
        if (! in_array($year, $years, true)) {
            $years[] = $year;
            $years = array_values(array_unique($years));
        }
        sort($years);

        if ($request->get('add_section')) {
            $maxFromRows = (int) CompanyReportRow::where('company_id', $id)->where('year', $year)->max('section');
            $maxFromTitles = (int) CompanyReportSectionTitle::where('company_id', $id)->where('year', $year)->max('section');
            $maxSection = max(4, $maxFromRows, $maxFromTitles);
            $newSection = $maxSection + 1;
            if ($newSection <= 255) {
                CompanyReportSectionTitle::updateOrCreate(
                    ['company_id' => $id, 'year' => $year, 'section' => $newSection],
                    ['title' => null]
                );
            }
            return redirect()->route('companies.report', ['id' => $id, 'year' => $year]);
        }

        $sectionsFromRows = CompanyReportRow::where('company_id', $id)->where('year', $year)->distinct()->pluck('section')->all();
        $sectionsFromTitles = CompanyReportSectionTitle::where('company_id', $id)->where('year', $year)->pluck('section')->all();
        $sectionNumbers = array_values(array_unique(array_merge([1, 2, 3, 4], $sectionsFromRows, $sectionsFromTitles)));
        sort($sectionNumbers);

        // 如果當前年份某個 section 完全沒有資料，但之前年份有，
        // 則自動從「最近有資料的年份」複製一份結構（只帶 label / 排序，不帶金額），
        // 讓新年份一打開就有同一套 row，可以依年份各自編輯 / 刪除。
        $monthKeys = CompanyReportRow::monthKeys();
        foreach ($sectionNumbers as $s) {
            $hasCurrent = CompanyReportRow::where('company_id', $id)
                ->where('year', $year)
                ->where('section', $s)
                ->exists();
            if ($hasCurrent) {
                continue;
            }
            $prevYear = CompanyReportRow::where('company_id', $id)
                ->where('section', $s)
                ->where('year', '<', $year)
                ->max('year');
            if (! $prevYear) {
                continue;
            }
            $templateRows = CompanyReportRow::where('company_id', $id)
                ->where('year', $prevYear)
                ->where('section', $s)
                ->orderBy('below_total')
                ->orderBy('row_order')
                ->orderBy('id')
                ->get();
            $order = 0;
            foreach ($templateRows as $tr) {
                $data = [
                    'company_id' => $id,
                    'year' => $year,
                    'section' => $s,
                    'row_order' => $order++,
                    'below_total' => $tr->below_total,
                    'label' => $tr->label,
                ];
                foreach ($monthKeys as $mk) {
                    $data[$mk] = null;
                }
                CompanyReportRow::create($data);
            }
        }

        // 每一年只顯示自己的 row；當年順序與「Total 上/下」依 below_total + row_order 儲存
        $rowsBySection = [];
        $totalAfterIndex = [];
        foreach ($sectionNumbers as $s) {
            $result = $this->buildRowsForSection($id, $s, $year, $monthKeys);
            $rowsBySection[$s] = $result['rows'];
            $totalAfterIndex[$s] = $result['total_after_index'];
        }

        $sectionTitles = CompanyReportSectionTitle::where('company_id', $id)->where('year', $year)->get()->keyBy('section');
        $defaultTitles = [1 => __('Part 1'), 2 => __('Part 2'), 3 => __('Part 3'), 4 => __('Part 4')];
        $sectionCounts = [];
        foreach ($sectionNumbers as $s) {
            if (! isset($defaultTitles[$s])) {
                $defaultTitles[$s] = __('Part') . ' ' . $s;
            }
            $sectionCounts[$s] = count($rowsBySection[$s] ?? []);
        }

        return view('companies.report', [
            'company' => $company,
            'companies' => $companies,
            'year' => $year,
            'years' => $years,
            'sectionNumbers' => $sectionNumbers,
            'rowsBySection' => $rowsBySection,
            'totalAfterIndex' => $totalAfterIndex,
            'sectionTitles' => $sectionTitles,
            'defaultTitles' => $defaultTitles,
            'sectionCounts' => $sectionCounts,
        ]);
    }

    /**
     * Delete a section (all rows + title) for this company/year. Redirects back to report.
     */
    public function deleteSection(Request $request, int $id): RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        if (! in_array($id, $companyIds, true)) {
            return redirect()->route('companies.index')->with('error', __('Not allowed to edit this company.'));
        }
        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }
        $year = (int) $request->get('year', date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $section = (int) $request->get('section', 0);
        if ($section < 1 || $section > 255) {
            return redirect()->route('companies.report', ['id' => $id, 'year' => $year])->with('error', __('Invalid section.'));
        }
        CompanyReportRow::where('company_id', $id)->where('year', $year)->where('section', $section)->delete();
        CompanyReportSectionTitle::where('company_id', $id)->where('year', $year)->where('section', $section)->delete();
        return redirect()->route('companies.report', ['id' => $id, 'year' => $year])->with('success', __('Section deleted.'));
    }

    /**
     * Save report rows for a company/year. POST rows by section.
     */
    public function store(Request $request, int $id): RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        if (! in_array($id, $companyIds, true)) {
            return redirect()->route('companies.index')->with('error', __('Not allowed to edit this company.'));
        }

        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }

        $year = (int) $request->input('year', date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $monthKeys = CompanyReportRow::monthKeys();
        $sections = [1, 2, 3, 4];
        foreach ($request->all() as $key => $val) {
            if (preg_match('/^section_(\d+)_(?:rows|title)$/', $key, $m)) {
                $s = (int) $m[1];
                if ($s >= 1 && $s <= 255 && ! in_array($s, $sections, true)) {
                    $sections[] = $s;
                }
            }
        }
        sort($sections);

        DB::transaction(function () use ($id, $year, $request, $sections, $monthKeys) {
            foreach ($sections as $section) {
                CompanyReportRow::where('company_id', $id)->where('year', $year)->where('section', $section)->delete();
                $rows = $request->input("section_{$section}_rows", []);
                $totalAfter = (int) $request->input("section_{$section}_total_after_index", 0);
                if (is_array($rows)) {
                    $order = 0;
                    foreach ($rows as $row) {
                        $label = isset($row['label']) ? (string) $row['label'] : '';
                        $belowTotal = $order >= $totalAfter;
                        $data = [
                            'company_id' => $id,
                            'year' => $year,
                            'section' => $section,
                            'row_order' => $order,
                            'below_total' => $belowTotal,
                            'label' => $label,
                        ];
                        foreach ($monthKeys as $mk) {
                            $data[$mk] = isset($row[$mk]) && $row[$mk] !== '' ? (float) $row[$mk] : null;
                        }
                        CompanyReportRow::create($data);
                        $order++;
                    }
                }
                $title = $request->input("section_{$section}_title", '');
                CompanyReportSectionTitle::updateOrCreate(
                    ['company_id' => $id, 'year' => $year, 'section' => $section],
                    ['title' => trim((string) $title) ?: null]
                );
            }
        });

        AuditLogger::log('company.report.saved', [
            'description' => 'Company report saved',
            'company_id' => $id,
            'year' => $year,
        ], $id);

        return redirect()->route('companies.report', ['id' => $id, 'year' => $year])->with('success', __('Report saved.'));
    }

    /**
     * Build report rows for one section: current year rows in display order (above total, then below total).
     * Empty rows for labels that exist in other years are appended above total.
     * Returns [ 'rows' => [...], 'total_after_index' => N ].
     */
    private function buildRowsForSection(int $companyId, int $section, int $year, array $monthKeys): array
    {
        $yearRows = CompanyReportRow::where('company_id', $companyId)
            ->where('year', $year)
            ->where('section', $section)
            ->orderBy('below_total')
            ->orderBy('row_order')
            ->orderBy('id')
            ->get();

        $above = $yearRows->where('below_total', false)->values();
        $below = $yearRows->where('below_total', true)->values();
        // 每一年只顯示該年的 rows；Total 上面是 below_total = false 的部分
        $totalAfterIndex = $above->count();
        $rows = $above->concat($below)->values()->all();

        return ['rows' => $rows, 'total_after_index' => $totalAfterIndex];
    }

    /** Years that have report rows or cashflow entries for this company */
    private function availableYears(int $companyId): array
    {
        $fromReport = CompanyReportRow::where('company_id', $companyId)->distinct()->pluck('year')->all();
        $fromCashflow = CashflowEntry::where('company_id', $companyId)->distinct()->selectRaw('YEAR(entry_date) as y')->pluck('y')->all();
        $years = array_unique(array_merge($fromReport, $fromCashflow));
        $years = array_filter($years);
        sort($years);
        return array_values($years);
    }

    /**
     * Total up all years - show one report with all years summed by label (read-only).
     */
    public function totalAllYears(int $id): View|RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        if (! in_array($id, $companyIds, true)) {
            return redirect()->route('companies.index')->with('error', __('Not allowed to view this company.'));
        }

        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }

        $years = $this->availableYears($id);
        $monthKeys = CompanyReportRow::monthKeys();
        $rowsBySection = [1 => [], 2 => [], 3 => [], 4 => []];

        foreach ([1, 2, 3, 4] as $section) {
            $rows = CompanyReportRow::where('company_id', $id)
                ->where('section', $section)
                ->orderBy('year')
                ->orderBy('row_order')
                ->orderBy('id')
                ->get();
            $byLabel = [];
            $labelOrder = [];
            foreach ($rows as $r) {
                $key = trim((string) ($r->label ?? ''));
                if ($key === '') {
                    $key = "\0";
                }
                if (! isset($byLabel[$key])) {
                    $labelOrder[] = $key;
                    $byLabel[$key] = array_fill(1, 12, 0.0);
                }
                foreach ($monthKeys as $idx => $mk) {
                    $v = $r->{$mk};
                    if ($v !== null) {
                        $byLabel[$key][$idx + 1] += (float) $v;
                    }
                }
            }
            foreach ($labelOrder as $labelKey) {
                $row = (object) [
                    'label' => $labelKey === "\0" ? '' : $labelKey,
                ];
                foreach ($monthKeys as $idx => $mk) {
                    $row->{$mk} = round($byLabel[$labelKey][$idx + 1], 2);
                }
                $rowsBySection[$section][] = $row;
            }
        }

        return view('companies.report_all_years', [
            'company' => $company,
            'years' => $years,
            'rowsBySection' => $rowsBySection,
        ]);
    }

    /**
     * Ordered label keys for one section (same order as totalAllYears view). Empty label stored as \0.
     */
    private function orderedLabelKeysForSection(int $companyId, int $section): array
    {
        $rows = CompanyReportRow::where('company_id', $companyId)
            ->where('section', $section)
            ->orderBy('year')
            ->orderBy('row_order')
            ->orderBy('id')
            ->get();
        $labelOrder = [];
        foreach ($rows as $r) {
            $key = trim((string) ($r->label ?? ''));
            if ($key === '') {
                $key = "\0";
            }
            if (! in_array($key, $labelOrder, true)) {
                $labelOrder[] = $key;
            }
        }
        return $labelOrder;
    }

    /**
     * Delete one aggregated row from Total all years (by section + row index, same order as view).
     */
    public function deleteAllYearsRow(Request $request, int $id): RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        if (! in_array($id, $companyIds, true)) {
            return redirect()->route('companies.index')->with('error', __('Not allowed to edit this company.'));
        }
        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }
        $section = (int) $request->input('section', 0);
        if ($section < 1 || $section > 4) {
            return redirect()->route('companies.report.all-years', $id)->with('error', __('Invalid section.'));
        }
        $rowIndex = (int) $request->input('row_index', -1);
        $labelOrder = $this->orderedLabelKeysForSection($id, $section);
        if ($rowIndex < 0 || $rowIndex >= count($labelOrder)) {
            return redirect()->route('companies.report.all-years', $id)->with('error', __('Invalid row.'));
        }
        $labelKey = $labelOrder[$rowIndex];
        $rows = CompanyReportRow::where('company_id', $id)->where('section', $section)->get();
        $idsToDelete = $rows->filter(function ($r) use ($labelKey) {
            $k = trim((string) ($r->label ?? ''));
            if ($k === '') {
                $k = "\0";
            }
            return $k === $labelKey;
        })->pluck('id')->all();
        $deleted = $idsToDelete ? CompanyReportRow::whereIn('id', $idsToDelete)->delete() : 0;
        return redirect()->route('companies.report.all-years', $id)->with('success', $deleted ? __('Row deleted from all years.') : __('No rows to delete.'));
    }

    /**
     * Export one company/year report. Default: HTML for Excel (styled). ?format=csv for plain CSV.
     */
    public function export(Request $request, int $id): Response|StreamedResponse|RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        if (! in_array($id, $companyIds, true)) {
            return redirect()->route('companies.index')->with('error', __('Not allowed to view this company.'));
        }

        $company = Company::find($id);
        if (! $company) {
            return redirect()->route('companies.index')->with('error', __('Company not found.'));
        }

        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $rowsBySection = [
            1 => CompanyReportRow::where('company_id', $id)->where('year', $year)->where('section', 1)->orderBy('row_order')->orderBy('id')->get(),
            2 => CompanyReportRow::where('company_id', $id)->where('year', $year)->where('section', 2)->orderBy('row_order')->orderBy('id')->get(),
            3 => CompanyReportRow::where('company_id', $id)->where('year', $year)->where('section', 3)->orderBy('row_order')->orderBy('id')->get(),
            4 => CompanyReportRow::where('company_id', $id)->where('year', $year)->where('section', 4)->orderBy('row_order')->orderBy('id')->get(),
        ];

        if ($request->get('format') === 'csv') {
            AuditLogger::log('company.report.export_csv', [
                'description' => 'Company report CSV export',
                'company_id' => $id,
                'year' => $year,
            ], $id);
            return $this->exportCsv($company, $year, $rowsBySection);
        }

        $filename = 'company_' . $company->code . '_transactions_' . $year . '_' . now()->format('Y-m-d_His') . '.xls';
        $html = view('companies.report_export', [
            'company' => $company,
            'year' => $year,
            'rowsBySection' => $rowsBySection,
        ])->render();

        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);

        AuditLogger::log('company.report.export_html', [
            'description' => 'Company report HTML/Excel export',
            'company_id' => $id,
            'year' => $year,
        ], $id);
        return response($bom . $html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function exportCsv($company, int $year, array $rowsBySection): StreamedResponse
    {
        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $monthKeys = CompanyReportRow::monthKeys();
        $filename = 'company_' . $company->code . '_transactions_' . $year . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rowsBySection, $monthLabels, $monthKeys) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            $writeSection = function (string $title, $rows, bool $addTotalRow = true) use ($out, $monthLabels, $monthKeys) {
                fputcsv($out, [$title]);
                fputcsv($out, array_merge(['No', 'Description'], $monthLabels));
                $totals = array_fill(0, 12, 0.0);
                foreach ($rows as $i => $r) {
                    $line = [($i + 1), (string) ($r->label ?? '')];
                    foreach ($monthKeys as $idx => $mk) {
                        $v = $r->$mk !== null ? (float) $r->$mk : 0.0;
                        $totals[$idx] += $v;
                        $line[] = number_format($v, 2, '.', ',');
                    }
                    fputcsv($out, $line);
                }
                if ($addTotalRow) {
                    fputcsv($out, array_merge(['Total', ''], array_map(fn ($v) => number_format($v, 2, '.', ','), $totals)));
                }
                return $totals;
            };
            $t1 = $writeSection('Part 1', $rowsBySection[1]);
            $pending = $writeSection('Part 2', $rowsBySection[2], false);
            fputcsv($out, array_merge(['Pending Amount', ''], array_map(fn ($v) => number_format($v, 2, '.', ','), $pending)));
            $p2Total = [];
            for ($i = 0; $i < 12; $i++) $p2Total[$i] = $t1[$i] + $pending[$i];
            fputcsv($out, array_merge(['Total', ''], array_map(fn ($v) => number_format($v, 2, '.', ','), $p2Total)));
            $writeSection('Part 3', $rowsBySection[3]);
            $writeSection('Part 4', $rowsBySection[4]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
