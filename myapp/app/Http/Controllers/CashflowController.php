<?php

namespace App\Http\Controllers;

use App\Models\CashflowEntry;
use App\Models\CashflowCategory;
use App\Models\CashflowExtraColumn;
use App\Models\Company;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashflowController extends Controller
{
    /**
     * Get company IDs the current user can access.
     */
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
     * Whether the current user can delete cashflow entries (admin/super_admin only).
     */
    private function canDelete(): bool
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    /**
     * List cashflow entries: all companies or filter by one company.
     * When viewing "all", computes monthly closing balances for "balance brought forward" on the 1st of each month.
     */
    public function index(Request $request): View
    {
        $companyIds = $this->allowedCompanyIds();
        $companyId = $request->integer('company_id');
        $dateFrom = $request->input('date_from', '');
        $dateTo = $request->input('date_to', '');
        $dateAll = $request->input('date_all', '0') === '1';
        $remark = trim((string) $request->input('remark', ''));

        $baseQuery = CashflowEntry::query();
        $closingBaseQuery = CashflowEntry::query();
        if ($companyId === 0) {
            $baseQuery->whereNull('company_id');
            $closingBaseQuery->whereNull('company_id');
        } else {
            if ($companyIds !== []) {
                $baseQuery->whereIn('company_id', $companyIds);
                $closingBaseQuery->whereIn('company_id', $companyIds);
            }
            if (in_array($companyId, $companyIds, true)) {
                $baseQuery->where('company_id', $companyId);
                $closingBaseQuery->where('company_id', $companyId);
            }
        }

        if (! $dateAll && $dateFrom === '' && $dateTo === '') {
            // 預設為當月
            $now = Carbon::now();
            $dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
            $dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
        }

        if (! $dateAll) {
            if ($dateFrom !== '') {
                $baseQuery->whereDate('entry_date', '>=', $dateFrom);
            }
            if ($dateTo !== '') {
                $baseQuery->whereDate('entry_date', '<=', $dateTo);
            }
        }
        if ($remark !== '') {
            $baseQuery->where('description', 'like', '%' . $remark . '%');
        }

        $query = (clone $baseQuery)->with(['company', 'user', 'extraValues'])
            // Show month days from 1 -> 31 (ascending)
            ->orderBy('entry_date', 'asc')
            // For same date, keep in input/creation order (older first)
            ->orderBy('id', 'asc');

        $entries = $query->paginate(50)->withQueryString();
        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_all' => $dateAll ? '1' : '0',
            'remark' => $remark,
        ];
        $extraColumns = CashflowExtraColumn::ordered()->get();

        // Monthly closing balances (up to end of that month) for "balance brought forward"
        // Track Total (amount_minor), Affin (affin_minor) and Xe+USDT (xe_minor+usdt_minor).
        $monthlyClosing = [
            'total_minor' => [],
            'affin_minor' => [],
            'xe_usdt_minor' => [],
        ];
        $raw = $closingBaseQuery->selectRaw('YEAR(entry_date) as y, MONTH(entry_date) as m, SUM(amount_minor) as sum_total_minor, SUM(COALESCE(affin_minor,0)) as sum_affin_minor, SUM(COALESCE(xe_minor,0) + COALESCE(usdt_minor,0)) as sum_xe_usdt_minor')
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get();
        $runningTotal = 0;
        $runningAffin = 0;
        $runningXeUsdt = 0;
        foreach ($raw as $r) {
            $runningTotal += (int) $r->sum_total_minor;
            $runningAffin += (int) $r->sum_affin_minor;
            $runningXeUsdt += (int) $r->sum_xe_usdt_minor;
            $k = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            $monthlyClosing['total_minor'][$k] = $runningTotal;
            $monthlyClosing['affin_minor'][$k] = $runningAffin;
            $monthlyClosing['xe_usdt_minor'][$k] = $runningXeUsdt;
        }

        $companies = Company::query()
            ->whereIn('id', $companyIds ?: [0])
            ->orderBy('name')
            ->get();

        return view('cashflow.index', [
            'entries' => $entries,
            'companies' => $companies,
            'currentCompanyId' => $companyId,
            'canDelete' => $this->canDelete(),
            'monthlyClosing' => $monthlyClosing,
            'extraColumns' => $extraColumns,
            'filters' => $filters,
        ]);
    }

    /**
     * Add a new column to the cashflow table.
     */
    public function storeColumn(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:64']]);
        $maxOrder = CashflowExtraColumn::query()->max('sort_order') ?? 0;
        $col = CashflowExtraColumn::create([
            'name' => $request->input('name'),
            'sort_order' => $maxOrder + 1,
        ]);
        $companyId = $request->integer('company_id');

        AuditLogger::log('cashflow.column.created', [
            'description' => 'Cashflow extra column created',
            'column_id' => $col->id,
            'name' => $col->name,
        ], $companyId > 0 ? $companyId : null);

        return redirect()->route('cashflow.index', $companyId > 0 ? ['company_id' => $companyId] : [])
            ->with('success', __('Column added.'));
    }

    /**
     * Remove a column from the cashflow table.
     */
    public function destroyColumn(int $id): RedirectResponse
    {
        $col = CashflowExtraColumn::find($id);
        if ($col) {
            $details = [
                'description' => 'Cashflow extra column deleted',
                'column_id' => $col->id,
                'name' => $col->name,
            ];
            $col->delete();
            AuditLogger::log('cashflow.column.deleted', $details);
        }

        return redirect()->back()->with('success', __('Column removed.'));
    }

    /**
     * Store/update rows from cashflow index (existing entries + new rows).
     * Total column is user-editable; amount_minor is taken from total value.
     */
    public function storeRows(Request $request): RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        $currentCompanyId = $request->integer('company_id');
        $entriesInput = $request->input('entries', []);
        $newRows = $request->input('new_rows', []);
        if (! is_array($entriesInput)) {
            $entriesInput = [];
        }
        if (! is_array($newRows)) {
            $newRows = [];
        }

        $hasDepCols = Schema::hasColumn('cashflow_entries', 'deposit_minor') && Schema::hasColumn('cashflow_entries', 'withdrawal_minor');

        $updated = 0;
        foreach ($entriesInput as $id => $row) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $entry = CashflowEntry::find($id);
            if (! $entry) {
                continue;
            }
            if ($entry->company_id !== null && $companyIds !== [] && ! in_array($entry->company_id, $companyIds, true)) {
                continue;
            }
            if ($entry->company_id === null && $currentCompanyId !== 0) {
                continue;
            }
            if ($entry->company_id !== null && $entry->company_id !== $currentCompanyId) {
                continue;
            }
            $depositVal = array_key_exists('deposit', $row) && $row['deposit'] !== '' && $row['deposit'] !== null ? (float) $row['deposit'] : null;
            $withdrawalVal = array_key_exists('withdrawal', $row) && $row['withdrawal'] !== '' && $row['withdrawal'] !== null ? (float) $row['withdrawal'] : null;
            $total = null;
            if (array_key_exists('total', $row) && $row['total'] !== '' && $row['total'] !== null) {
                $total = (float) $row['total'];
            } else {
                $total = ((float) ($depositVal ?? 0)) - ((float) ($withdrawalVal ?? 0));
            }
            $amountMinor = (int) round($total * 100);
            $payload = [
                'entry_date' => $row['entry_date'] ?? $entry->entry_date->format('Y-m-d'),
                'amount_minor' => $amountMinor,
                'base_amount_minor' => $amountMinor,
                'affin_minor' => (int) round((float) ($row['affin'] ?? 0) * 100),
                'usdt_minor' => (int) round((float) ($row['xe_usdt'] ?? 0) * 100),
                'description' => $row['description'] ?? $entry->description,
            ];
            if ($hasDepCols) {
                $payload['deposit_minor'] = $depositVal !== null ? (int) round($depositVal * 100) : null;
                $payload['withdrawal_minor'] = $withdrawalVal !== null ? (int) round($withdrawalVal * 100) : null;
            }
            $entry->update($payload);
            $extra = $row['extra'] ?? [];
            foreach ($extra as $colId => $val) {
                $colId = (int) $colId;
                if ($colId < 1) {
                    continue;
                }
                $entry->extraValues()->updateOrCreate(
                    ['cashflow_extra_column_id' => $colId],
                    ['value_minor' => (int) round((float) $val * 100)]
                );
            }
            $updated++;
        }

        $created = 0;
        foreach ($newRows as $row) {
            $entryDate = $row['entry_date'] ?? null;
            if (! $entryDate) {
                continue;
            }
            $companyId = $currentCompanyId > 0 ? $currentCompanyId : null;
            if ($companyId !== null && $companyIds !== [] && ! in_array($companyId, $companyIds, true)) {
                continue;
            }
            $company = $companyId ? Company::find($companyId) : null;
            $baseCurrency = $company ? strtoupper($company->base_currency ?? 'USD') : 'USD';
            $depositVal = array_key_exists('deposit', $row) && $row['deposit'] !== '' && $row['deposit'] !== null ? (float) $row['deposit'] : null;
            $withdrawalVal = array_key_exists('withdrawal', $row) && $row['withdrawal'] !== '' && $row['withdrawal'] !== null ? (float) $row['withdrawal'] : null;
            $total = null;
            if (array_key_exists('total', $row) && $row['total'] !== '' && $row['total'] !== null) {
                $total = (float) $row['total'];
            } else {
                $total = ((float) ($depositVal ?? 0)) - ((float) ($withdrawalVal ?? 0));
            }
            $amountMinor = (int) round($total * 100);
            $description = $row['description'] ?? '';
            $createPayload = [
                'user_id' => Auth::id(),
                'company_id' => $companyId,
                'entry_date' => $entryDate,
                'category' => 'Cashflow',
                'currency' => $baseCurrency,
                'amount_minor' => $amountMinor,
                'fx_rate_to_base' => 1,
                'base_amount_minor' => $amountMinor,
                'affin_minor' => (int) round((float) ($row['affin'] ?? 0) * 100),
                'xe_minor' => 0,
                'usdt_minor' => (int) round((float) ($row['xe_usdt'] ?? 0) * 100),
                'description' => $description,
            ];
            if ($hasDepCols) {
                $createPayload['deposit_minor'] = $depositVal !== null ? (int) round($depositVal * 100) : null;
                $createPayload['withdrawal_minor'] = $withdrawalVal !== null ? (int) round($withdrawalVal * 100) : null;
            }
            CashflowEntry::create($createPayload);
            $created++;
        }

        $msg = $updated > 0 || $created > 0 ? __('Saved.') : __('No rows to save.');

        if ($updated > 0 || $created > 0) {
            AuditLogger::log('cashflow.rows.saved', [
                'description' => 'Cashflow rows saved from index screen',
                'company_id' => $currentCompanyId > 0 ? $currentCompanyId : null,
                'updated_rows' => $updated,
                'created_rows' => $created,
                'filters' => [
                    'date_from' => $request->input('date_from'),
                    'date_to' => $request->input('date_to'),
                    'date_all' => $request->input('date_all'),
                    'remark' => $request->input('remark'),
                ],
            ], $currentCompanyId > 0 ? $currentCompanyId : null);
        }
        return redirect()
            ->route('cashflow.index', array_filter([
                'company_id' => $currentCompanyId > 0 ? $currentCompanyId : null,
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'date_all' => $request->input('date_all'),
                'remark' => $request->input('remark'),
            ]))
            ->with('success', $msg);
    }

    /**
     * Show form to create a new cashflow entry.
     */
    public function create(Request $request): View
    {
        $companyIds = $this->allowedCompanyIds();
        $companies = Company::query()
            ->whereIn('id', $companyIds ?: [0])
            ->orderBy('name')
            ->get();

        $presetCompanyId = $request->integer('company_id');
        if ($presetCompanyId > 0 && ! in_array($presetCompanyId, $companyIds, true)) {
            $presetCompanyId = 0;
        }

        $categories = CashflowCategory::ordered()->get();

        return view('cashflow.edit', [
            'entry' => null,
            'companies' => $companies,
            'presetCompanyId' => $presetCompanyId,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new cashflow entry.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        $rules = [
            'company_id' => ['required', 'integer', Rule::in($companyIds)],
            'entry_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
        $company = Company::find($request->input('company_id'));
        $baseCurrency = $company ? strtoupper($company->base_currency ?? 'USD') : 'USD';
        if ($baseCurrency === 'MYR') {
            $request->merge(['fx_rate_to_base' => 1]);
        } else {
            $rules['fx_rate_to_base'] = ['required', 'numeric', 'min:0'];
        }
        $valid = $request->validate($rules);

        $valid['user_id'] = Auth::id();
        $valid['amount_minor'] = (int) round((float) $valid['amount'] * 100);
        $valid['fx_rate_to_base'] = (float) ($request->input('fx_rate_to_base', 1));
        $valid['base_amount_minor'] = (int) round($valid['amount_minor'] * $valid['fx_rate_to_base']);
        unset($valid['amount']);

        $entry = CashflowEntry::create($valid);

        AuditLogger::log('cashflow.entry.created', [
            'description' => 'Cashflow entry created (form)',
            'entry_id' => $entry->id,
            'company_id' => $entry->company_id,
            'category' => $entry->category,
            'currency' => $entry->currency,
            'amount' => $entry->amount_minor / 100,
        ], $entry->company_id);

        return redirect()
            ->route('cashflow.index', ['company_id' => $valid['company_id']])
            ->with('success', __('Cashflow entry created.'));
    }

    /**
     * Show form to edit an existing entry.
     */
    public function edit(int $id): View|RedirectResponse
    {
        $entry = CashflowEntry::with('company')->find($id);
        if (! $entry) {
            return redirect()->route('cashflow.index')->with('error', __('Entry not found.'));
        }

        $companyIds = $this->allowedCompanyIds();
        if ($entry->company_id !== null && $companyIds !== [] && ! in_array($entry->company_id, $companyIds, true)) {
            return redirect()->route('cashflow.index')->with('error', __('Not allowed to edit this entry.'));
        }

        $companies = Company::query()
            ->whereIn('id', $companyIds ?: ($entry->company_id ? [$entry->company_id] : [0]))
            ->orderBy('name')
            ->get();

        $company = $entry->company;
        $baseCurrency = $company ? strtoupper($company->base_currency ?? 'USD') : 'USD';
        $needFxRate = $baseCurrency !== 'MYR';

        $categories = CashflowCategory::ordered()->get();
        $extraColumns = CashflowExtraColumn::ordered()->get();
        $entry->load('extraValues');

        return view('cashflow.edit', [
            'entry' => $entry,
            'companies' => $companies,
            'presetCompanyId' => $entry->company_id,
            'baseCurrency' => $baseCurrency,
            'needFxRate' => $needFxRate,
            'categories' => $categories,
            'extraColumns' => $extraColumns,
        ]);
    }

    /**
     * Update an existing entry.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $entry = CashflowEntry::find($id);
        if (! $entry) {
            return redirect()->route('cashflow.index')->with('error', __('Entry not found.'));
        }

        $companyIds = $this->allowedCompanyIds();
        if ($entry->company_id !== null && $companyIds !== [] && ! in_array($entry->company_id, $companyIds, true)) {
            return redirect()->route('cashflow.index')->with('error', __('Not allowed to edit this entry.'));
        }

        $company = $entry->company;
        $baseCurrency = $company ? strtoupper($company->base_currency ?? 'USD') : 'USD';
        $rules = [
            'entry_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
        if ($baseCurrency === 'MYR') {
            $request->merge(['fx_rate_to_base' => 1]);
        } else {
            $rules['fx_rate_to_base'] = ['required', 'numeric', 'min:0'];
        }
        $valid = $request->validate($rules);

        $valid['amount_minor'] = (int) round((float) $valid['amount'] * 100);
        $valid['fx_rate_to_base'] = (float) ($request->input('fx_rate_to_base', 1));
        $valid['base_amount_minor'] = (int) round($valid['amount_minor'] * $valid['fx_rate_to_base']);
        unset($valid['amount']);
        unset($valid['company_id']);
        $entry->update($valid);

        AuditLogger::log('cashflow.entry.updated', [
            'description' => 'Cashflow entry updated (form)',
            'entry_id' => $entry->id,
            'company_id' => $entry->company_id,
            'category' => $entry->category,
            'currency' => $entry->currency,
            'amount' => $entry->amount_minor / 100,
        ], $entry->company_id);

        $extra = $request->input('extra', []);
        foreach ($extra as $columnId => $value) {
            $columnId = (int) $columnId;
            if ($columnId < 1) {
                continue;
            }
            $valueMinor = (int) round((float) $value * 100);
            $entry->extraValues()->updateOrCreate(
                ['cashflow_extra_column_id' => $columnId],
                ['value_minor' => $valueMinor]
            );
        }

        return redirect()
            ->route('cashflow.index', $entry->company_id !== null ? ['company_id' => $entry->company_id] : [])
            ->with('success', __('Cashflow entry updated.'));
    }

    /**
     * Delete an entry (admin/super_admin only).
     */
    public function destroy(int $id): RedirectResponse
    {
        if (! $this->canDelete()) {
            return redirect()->route('cashflow.index')->with('error', __('You do not have permission to delete.'));
        }

        $entry = CashflowEntry::find($id);
        if (! $entry) {
            return redirect()->route('cashflow.index')->with('error', __('Entry not found.'));
        }

        $companyIds = $this->allowedCompanyIds();
        if ($entry->company_id !== null && $companyIds !== [] && ! in_array($entry->company_id, $companyIds, true)) {
            return redirect()->route('cashflow.index')->with('error', __('Not allowed to delete this entry.'));
        }

        $companyId = $entry->company_id;

        AuditLogger::log('cashflow.entry.deleted', [
            'description' => 'Cashflow entry deleted',
            'entry_id' => $entry->id,
            'company_id' => $companyId,
        ], $companyId);
        $entry->delete();

        return redirect()
            ->route('cashflow.index', $companyId !== null ? ['company_id' => $companyId] : [])
            ->with('success', __('Cashflow entry deleted.'));
    }

    /**
     * Print view for one entry.
     */
    public function print(int $id): View|RedirectResponse
    {
        $entry = CashflowEntry::with(['company', 'user'])->find($id);
        if (! $entry) {
            return redirect()->route('cashflow.index')->with('error', __('Entry not found.'));
        }

        $companyIds = $this->allowedCompanyIds();
        if ($entry->company_id !== null && $companyIds !== [] && ! in_array($entry->company_id, $companyIds, true)) {
            return redirect()->route('cashflow.index')->with('error', __('Not allowed to view this entry.'));
        }

        return view('cashflow.print', ['entry' => $entry]);
    }

    /**
     * Export cashflow as Excel (XLSX) using PhpSpreadsheet.
     * Layout与页面相同：Date, Deposit, Withdrawal, AFFIN, Total, Xe USDT, Remark，
     * 第一行显示 Base currency；负数使用红色括号显示。
     */
    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $companyIds = $this->allowedCompanyIds();
        $companyId = $request->integer('company_id');

        $query = CashflowEntry::query()
            ->with('company')
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        if ($companyId === 0) {
            $query->whereNull('company_id');
        } else {
            if ($companyIds !== []) {
                $query->whereIn('company_id', $companyIds);
            }
            if (in_array($companyId, $companyIds, true)) {
                $query->where('company_id', $companyId);
            }
        }

        $entries = $query->get();

        // Decide base currency label for file (not a column; values are already in base).
        $baseCurrency = 'MYR';
        if ($companyId > 0) {
            $company = Company::find($companyId);
            if ($company) {
                $baseCurrency = strtoupper($company->base_currency ?? 'MYR');
            }
        }

        $filename = 'cashflow_' . ($companyId > 0 ? 'company_' . $companyId : 'master') . '_' . $baseCurrency . '_' . now()->format('Y-m-d_His') . '.xlsx';

        // Monthly closing for Balance bring forward rows (use full ledger for this scope).
        $closingBase = CashflowEntry::query();
        if ($companyId === 0) {
            $closingBase->whereNull('company_id');
        } else {
            if ($companyIds !== []) {
                $closingBase->whereIn('company_id', $companyIds);
            }
            if (in_array($companyId, $companyIds, true)) {
                $closingBase->where('company_id', $companyId);
            }
        }
        $monthlyClosing = [
            'total_minor' => [],
            'affin_minor' => [],
            'xe_usdt_minor' => [],
        ];
        $raw = $closingBase->selectRaw('YEAR(entry_date) as y, MONTH(entry_date) as m, SUM(amount_minor) as sum_total_minor, SUM(COALESCE(affin_minor,0)) as sum_affin_minor, SUM(COALESCE(xe_minor,0) + COALESCE(usdt_minor,0)) as sum_xe_usdt_minor')
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get();
        $runningTotal = 0;
        $runningAffin = 0;
        $runningXeUsdt = 0;
        foreach ($raw as $r) {
            $runningTotal += (int) $r->sum_total_minor;
            $runningAffin += (int) $r->sum_affin_minor;
            $runningXeUsdt += (int) $r->sum_xe_usdt_minor;
            $k = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            $monthlyClosing['total_minor'][$k] = $runningTotal;
            $monthlyClosing['affin_minor'][$k] = $runningAffin;
            $monthlyClosing['xe_usdt_minor'][$k] = $runningXeUsdt;
        }

        AuditLogger::log('cashflow.export', [
            'description' => 'Cashflow export generated',
            'company_id' => $companyId > 0 ? $companyId : null,
            'base_currency' => $baseCurrency,
            'rows' => $entries->count(),
        ], $companyId > 0 ? $companyId : null);

        return response()->streamDownload(function () use ($entries, $monthlyClosing, $baseCurrency) {
            $sheet = new Spreadsheet();
            $ws = $sheet->getActiveSheet();
            $ws->setTitle('Cashflow');

            // 列映射
            $col = [
                'date' => 'A',
                'deposit' => 'B',
                'withdrawal' => 'C',
                'affin' => 'D',
                'total' => 'E',
                'xe' => 'F',
                'remark' => 'G',
            ];

            $row = 1;
            // Base currency 行
            $ws->setCellValue($col['date'] . $row, 'Base currency');
            $ws->setCellValue($col['deposit'] . $row, $baseCurrency);
            $row++;

            // 表头
            $headers = ['Date', 'Deposit', 'Withdrawal', 'AFFIN', 'Total', 'Xe USDT', 'Remark'];
            $ws->fromArray($headers, null, $col['date'] . $row);
            $headerStyle = $ws->getStyle($col['date'] . $row . ':' . $col['remark'] . $row);
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;

            $numFormat = '#,##0.00;[Red](#,##0.00)'; // 正常 / 负数红+括号

            $totalDepositMinor = 0;
            $totalWithdrawalMinor = 0;
            $totalAffinMinor = 0;
            $totalXeUsdtMinor = 0;
            $printedBf = [];

            foreach ($entries as $e) {
                $monthKey = $e->entry_date->format('Y-m');
                $prevMonthKey = $e->entry_date->copy()->subMonth()->format('Y-m');
                $bfTotalMinor = $monthlyClosing['total_minor'][$prevMonthKey] ?? null;
                $bfAffinMinor = $monthlyClosing['affin_minor'][$prevMonthKey] ?? null;
                $bfXeUsdtMinor = $monthlyClosing['xe_usdt_minor'][$prevMonthKey] ?? null;
                $bfLabel = $e->entry_date->copy()->subMonth()->format('MY');

                if (! in_array($monthKey, $printedBf, true) && ($bfTotalMinor !== null || $bfAffinMinor !== null || $bfXeUsdtMinor !== null)) {
                    $ws->setCellValue($col['date'] . $row, $e->entry_date->copy()->startOfMonth()->format('Y-m-d'));
                    if ($bfAffinMinor !== null) {
                        $ws->setCellValue($col['affin'] . $row, $bfAffinMinor / 100);
                    }
                    if ($bfTotalMinor !== null) {
                        $ws->setCellValue($col['total'] . $row, $bfTotalMinor / 100);
                    }
                    if ($bfXeUsdtMinor !== null) {
                        $ws->setCellValue($col['xe'] . $row, $bfXeUsdtMinor / 100);
                    }
                    $ws->setCellValue($col['remark'] . $row, 'Balance bring forward ' . $bfLabel);
                    $ws->getStyle($col['affin'] . $row . ':' . $col['xe'] . $row)
                        ->getNumberFormat()->setFormatCode($numFormat);
                    $row++;
                    $printedBf[] = $monthKey;
                }

                // 对于新的记录：只有当用户有填写 Deposit / Withdrawals 才会在 *_minor 里有值；
                // 没填就保持 0，不再从 Total 推回去。
                $amtMinor = $e->amount_minor;
                $depositMinor = $e->deposit_minor !== null ? (int) $e->deposit_minor : 0;
                $withdrawalMinor = $e->withdrawal_minor !== null ? (int) $e->withdrawal_minor : 0;
                $affinMinor = (int) ($e->affin_minor ?? 0);
                $xeMinor = (int) (($e->xe_minor ?? 0) + ($e->usdt_minor ?? 0));

                $totalDepositMinor += $depositMinor;
                $totalWithdrawalMinor += $withdrawalMinor;
                $totalAffinMinor += $affinMinor;
                $totalXeUsdtMinor += $xeMinor;

                $ws->setCellValue($col['date'] . $row, $e->entry_date->format('Y-m-d'));
                $ws->setCellValue($col['deposit'] . $row, $depositMinor / 100);
                // Withdrawal 用负数，让格式自动显示红色括号
                $ws->setCellValue($col['withdrawal'] . $row, $withdrawalMinor > 0 ? -1 * ($withdrawalMinor / 100) : 0);
                $ws->setCellValue($col['affin'] . $row, $affinMinor / 100);
                $ws->setCellValue($col['total'] . $row, $amtMinor / 100);
                $ws->setCellValue($col['xe'] . $row, $xeMinor / 100);
                $ws->setCellValue($col['remark'] . $row, $e->description ?? '');

                $ws->getStyle($col['deposit'] . $row . ':' . $col['xe'] . $row)
                    ->getNumberFormat()->setFormatCode($numFormat);

                $row++;
            }

            // Total 行
            $ws->setCellValue($col['date'] . $row, 'Total');
            $ws->setCellValue($col['deposit'] . $row, $totalDepositMinor / 100);
            $ws->setCellValue($col['withdrawal'] . $row, $totalWithdrawalMinor > 0 ? -1 * ($totalWithdrawalMinor / 100) : 0);
            $ws->setCellValue($col['affin'] . $row, $totalAffinMinor / 100);
            $ws->setCellValue($col['total'] . $row, array_sum($entries->pluck('amount_minor')->all()) / 100);
            $ws->setCellValue($col['xe'] . $row, $totalXeUsdtMinor / 100);

            $ws->getStyle($col['deposit'] . $row . ':' . $col['xe'] . $row)
                ->getNumberFormat()->setFormatCode($numFormat);
            $ws->getStyle($col['date'] . $row . ':' . $col['xe'] . $row)
                ->getFont()->setBold(true);

            // 自动列宽
            foreach ($col as $letter) {
                $ws->getColumnDimension($letter)->setAutoSize(true);
            }

            $writer = new Xlsx($sheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
