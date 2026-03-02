<?php

namespace App\Http\Controllers;

use App\Models\CashflowEntry;
use App\Models\Company;
use App\Models\CompanyReportRow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private function allowedCompanyIds(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [];
        }
        if ($user->all_companies) {
            return Company::query()->pluck('id')->all();
        }
        return $user->companies()->pluck('companies.id')->all();
    }

    /**
     * Dashboard: cashflow (pie deposit/withdraw, horizontal columns), companies (area Part1, line Part2).
     */
    public function index(Request $request): View
    {
        $companyIds = $this->allowedCompanyIds();

        $companies = collect();
        if ($companyIds !== []) {
            $companies = Company::query()
                ->whereIn('id', $companyIds)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'base_currency']);
        }

        $end = Carbon::today()->endOfMonth();
        $start = Carbon::today()->subMonths(11)->startOfMonth();

        $monthlyTotals = [];
        $monthlyByCompany = [];
        $depositTotalMinor = 0;
        $withdrawalTotalMinor = 0;
        $columnTotals = ['Affin' => 0, 'XE USDT' => 0, 'Total' => 0];

        if ($companyIds !== []) {
            $baseQuery = CashflowEntry::query()
                ->whereBetween('entry_date', [$start, $end])
                ->where(function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)->orWhereNull('company_id');
                });

            $rows = $baseQuery
                ->select(
                    DB::raw('YEAR(entry_date) as y'),
                    DB::raw('MONTH(entry_date) as m'),
                    DB::raw('COALESCE(SUM(base_amount_minor), 0) as total_minor')
                )
                ->groupBy('y', 'm')
                ->orderBy('y')
                ->orderBy('m')
                ->get();

            foreach ($rows as $r) {
                $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
                $monthlyTotals[$key] = (int) $r->total_minor;
            }

            $byCompany = $baseQuery
                ->select(
                    'company_id',
                    DB::raw('YEAR(entry_date) as y'),
                    DB::raw('MONTH(entry_date) as m'),
                    DB::raw('COALESCE(SUM(base_amount_minor), 0) as total_minor')
                )
                ->groupBy('company_id', 'y', 'm')
                ->orderBy('company_id')
                ->orderBy('y')
                ->orderBy('m')
                ->get();

            foreach ($byCompany as $r) {
                $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
                $cid = (int) $r->company_id;
                if (! isset($monthlyByCompany[$key])) {
                    $monthlyByCompany[$key] = [];
                }
                $monthlyByCompany[$key][$cid] = (int) $r->total_minor;
            }

            $dw = CashflowEntry::query()
                ->whereBetween('entry_date', [$start, $end])
                ->where(function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)->orWhereNull('company_id');
                })
                ->select(
                    DB::raw('COALESCE(SUM(deposit_minor), 0) as d'),
                    DB::raw('COALESCE(SUM(withdrawal_minor), 0) as w')
                )
                ->first();
            $depositTotalMinor = (int) ($dw->d ?? 0);
            $withdrawalTotalMinor = (int) ($dw->w ?? 0);

            $cols = CashflowEntry::query()
                ->whereBetween('entry_date', [$start, $end])
                ->where(function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)->orWhereNull('company_id');
                })
                ->select(
                    DB::raw('COALESCE(SUM(affin_minor), 0) as affin'),
                    DB::raw('COALESCE(SUM(xe_minor), 0) as xe'),
                    DB::raw('COALESCE(SUM(usdt_minor), 0) as usdt')
                )
                ->first();
            $columnTotals['Affin'] = (int) ($cols->affin ?? 0);
            $columnTotals['XE USDT'] = (int) ($cols->xe ?? 0) + (int) ($cols->usdt ?? 0);
            $columnTotals['Total'] = array_sum($monthlyTotals);
        }

        $labels = [];
        $totalsData = [];
        for ($i = 0; $i < 12; $i++) {
            $d = $start->copy()->addMonths($i);
            $key = $d->format('Y-m');
            $labels[] = $d->format('M Y');
            $totalsData[] = isset($monthlyTotals[$key]) ? round($monthlyTotals[$key] / 100, 2) : 0;
        }

        $companySeries = [];
        foreach ($companies as $c) {
            $series = [];
            for ($i = 0; $i < 12; $i++) {
                $d = $start->copy()->addMonths($i);
                $key = $d->format('Y-m');
                $v = $monthlyByCompany[$key][$c->id] ?? 0;
                $series[] = round($v / 100, 2);
            }
            $companySeries[] = [
                'name' => $c->name,
                'data' => $series,
            ];
        }

        $baseCurrency = $companies->first()?->base_currency ?? 'MYR';

        $currentYear = (int) date('Y');
        $reportYear = $companyIds !== []
            ? (int) (CompanyReportRow::whereIn('company_id', $companyIds)->max('year') ?? $currentYear)
            : $currentYear;

        $monthKeys = CompanyReportRow::monthKeys();
        $end = Carbon::today()->endOfMonth();
        $last6Months = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = $end->copy()->subMonths($i);
            $last6Months[] = [
                'year' => (int) $d->format('Y'),
                'month' => (int) $d->format('n'),
                'label' => $d->format('M Y'),
            ];
        }
        $part1Last6Labels = array_column($last6Months, 'label');
        $part2Labels = $part1Last6Labels;
        $part1Last6Series = [];
        $part2Series = [];
        $yearsNeeded = array_unique(array_column($last6Months, 'year'));

        foreach ($companies as $c) {
            $part1ByYearMonth = [];
            $part2ByYearMonth = [];
            foreach ($yearsNeeded as $yr) {
                $part1ByYearMonth[$yr] = array_fill(1, 12, 0.0);
                $part2ByYearMonth[$yr] = array_fill(1, 12, 0.0);
                $rows1 = CompanyReportRow::where('company_id', $c->id)->where('year', $yr)->where('section', 1)->get();
                $rows2 = CompanyReportRow::where('company_id', $c->id)->where('year', $yr)->where('section', 2)->get();
                foreach ($rows1 as $r) {
                    foreach ($monthKeys as $idx => $mk) {
                        $v = $r->{$mk};
                        if ($v !== null) {
                            $part1ByYearMonth[$yr][$idx + 1] += (float) $v;
                        }
                    }
                }
                foreach ($rows2 as $r) {
                    foreach ($monthKeys as $idx => $mk) {
                        $v = $r->{$mk};
                        if ($v !== null) {
                            $part2ByYearMonth[$yr][$idx + 1] += (float) $v;
                        }
                    }
                }
            }
            $part1Last6 = [];
            $part2Last6 = [];
            foreach ($last6Months as $cell) {
                $yr = $cell['year'];
                $m = $cell['month'];
                $p1 = $part1ByYearMonth[$yr][$m] ?? 0;
                $p2 = $part2ByYearMonth[$yr][$m] ?? 0;
                $part1Last6[] = round($p1, 2);
                $part2Last6[] = round($p1 + $p2, 2);
            }
            $part1Last6Series[] = ['name' => $c->name, 'data' => $part1Last6];
            $part2Series[] = ['name' => $c->name, 'data' => $part2Last6];
        }

        return view('dashboard', [
            'companies' => $companies,
            'labels' => $labels,
            'totalsData' => $totalsData,
            'companySeries' => $companySeries,
            'baseCurrency' => $baseCurrency,
            'depositTotalMinor' => $depositTotalMinor,
            'withdrawalTotalMinor' => $withdrawalTotalMinor,
            'columnTotals' => $columnTotals,
            'reportYear' => $reportYear,
            'part1Last6Labels' => $part1Last6Labels,
            'part1Last6Series' => $part1Last6Series,
            'part2Labels' => $part2Labels,
            'part2Series' => $part2Series,
        ]);
    }
}
