<x-app-layout>
    <x-slot name="header">
        {{ $company->name }} – {{ __('Total all years') }}
    </x-slot>
    <style>
        /* 大屏：内容 min-width 触发 content-layer 左右滚；小屏：100% 宽，全部往下排，表内左右滚 */
        .report-page-root {
            width: 100%;
            max-width: 100%;
            min-width: 720px;
            box-sizing: border-box;
        }
        .report-page-root * { box-sizing: border-box; }
        .report-page-root .report-header-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .report-page-root .report-actions-top {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }
        .report-scroll-box {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            max-height: none;
        }
        .report-table-wrap {
            width: 100%;
            min-width: 0;
            padding-bottom: 10px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 768px) {
            .report-page-root { min-width: 0; width: 100%; }
            .report-page-root .report-header-row { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .report-page-root .report-actions-top { width: 100%; justify-content: flex-start; }
        }
        .report-table {
            font-size: .8rem;
            border: none;
            border-collapse: separate !important;
            border-spacing: 0;
            width: max-content;
            min-width: 100%;
            table-layout: fixed;
        }
        .report-table .cf-th,
        .report-table .cf-td {
            border-radius: 8px;
            overflow: hidden;
            box-sizing: border-box;
        }
        .report-table .cf-th {
            padding: .35rem .4rem !important;
            background: transparent;
            border: none !important;
            font-weight: 600;
        }
        .report-table .cf-td {
            padding: .2rem .25rem !important;
            background: transparent;
            border-top: 1px solid rgba(209,213,219,.5);
            border-bottom: 1px solid rgba(209,213,219,.5);
            border-left: none;
            border-right: none;
            vertical-align: top;
        }
        .report-table tbody tr:first-child .cf-td {
            border-top: 1px solid rgba(209,213,219,.6);
        }
        .report-table .cf-tfoot .cf-td {
            font-weight: 600;
            border-top: 1px solid rgba(156,163,175,.7);
        }
        .report-table thead th:first-child { width: 4rem !important; }
        .all-years-row-del {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            padding: 0;
            margin-left: .25rem;
            font-size: 1rem;
            line-height: 1;
            color: #b91c1c;
            background: transparent;
            border: 1px solid rgba(185,28,28,.4);
            border-radius: 4px;
            cursor: pointer;
        }
        .all-years-row-del:hover {
            color: #fff;
            background: #b91c1c;
        }
        .report-table thead th:nth-child(2) { width: 140px !important; }
        .report-table thead th:nth-child(n+3) { width: 5rem !important; }
        .report-table .cf-td--left {
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: normal;
        }
        .report-table .cf-td--amount {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: right;
        }
        .report-part-wrap {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 900px) {
            .report-page-root .report-header-row { align-items: flex-start; }
            .report-page-root .report-actions-top {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
    @php
        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $monthKeys = \App\Models\CompanyReportRow::monthKeys();
        $rowsBySection = $rowsBySection ?? [1 => [], 2 => [], 3 => [], 4 => []];
        $part1Totals = [];
        $part2Pending = [];
        $part2Totals = [];
        $part3Totals = [];
        $part4Totals = [];
        foreach ($monthKeys as $idx => $mk) {
            $m = $idx + 1;
            $part1Totals[$m] = 0;
            foreach ($rowsBySection[1] as $r) { $part1Totals[$m] += (float)($r->$mk ?? 0); }
            $part2Pending[$m] = 0;
            foreach ($rowsBySection[2] as $r) { $part2Pending[$m] += (float)($r->$mk ?? 0); }
            $part2Totals[$m] = $part1Totals[$m] + $part2Pending[$m];
            $part3Totals[$m] = 0;
            foreach ($rowsBySection[3] as $r) { $part3Totals[$m] += (float)($r->$mk ?? 0); }
            $part4Totals[$m] = 0;
            foreach ($rowsBySection[4] as $r) { $part4Totals[$m] += (float)($r->$mk ?? 0); }
        }
    @endphp
    <div class="report-page-root max-w-full mx-auto w-full pb-12">
        <div class="report-scroll-box">
        @if(session('error'))
            <p class="text-sm text-red-700 mb-4">{{ session('error') }}</p>
        @endif
        @if(session('success'))
            <p class="text-sm text-green-700 mb-4">{{ session('success') }}</p>
        @endif

        {{-- Single form for delete row, submitted by JS --}}
        <form id="all-years-delete-form" method="post" action="" style="display:none;" data-action="{{ route('companies.report.all-years.delete-row', $company->id) }}" data-confirm="{{ e(__('Delete this row from all years?')) }}">
            @csrf
            <input type="hidden" name="section" value="">
            <input type="hidden" name="row_index" value="">
        </form>

        <div class="report-header-row mb-6">
            <div>
                <a href="{{ route('companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">← {{ __('Companies') }}</a>
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Transactions') }}</div>
                <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ $company->name }} – {{ __('Total all years') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('All years summed by row label. Read-only.') }}</p>
            </div>
            <div class="report-actions-top flex flex-wrap gap-2">
                @foreach($years as $y)
                    <a href="{{ route('companies.report', ['id' => $company->id, 'year' => $y]) }}" class="user-new-cell">
                        <span class="user-new-link">{{ $y }}</span>
                    </a>
                @endforeach
                <a href="{{ route('companies.report', $company->id) }}" class="user-new-cell">
                    <span class="user-new-link">{{ __('Monthly report') }}</span>
                </a>
                <a href="{{ route('cashflow.index', ['company_id' => $company->id]) }}" class="user-new-cell">
                    <span class="user-new-link">{{ __('Cashflow') }}</span>
                </a>
            </div>
        </div>

        <div class="content-layer p-4">
            {{-- Part 1 --}}
            <div class="report-part-wrap mb-8">
                <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('Part 1') }} – {{ __('Year/Month') }}</h2>
                <div class="report-table-wrap overflow-x-auto">
                    <table class="cf-table report-table w-full">
                        <thead>
                            <tr>
                                <th class="cf-th" style="width:4rem;">{{ __('No') }}</th>
                                <th class="cf-th cf-td--left" style="min-width:140px;">{{ __('Description') }}</th>
                                @foreach($monthLabels as $m)
                                    <th class="cf-th" style="min-width:5rem;">{{ $m }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rowsBySection[1] as $i => $row)
                                <tr>
                                    <td class="cf-td">
                                        {{ $i + 1 }}
                                        <button type="button" class="all-years-row-del" title="{{ __('Delete row') }}" data-section="1" data-row-index="{{ $i }}">×</button>
                                    </td>
                                    <td class="cf-td cf-td--left">{{ $row->label }}</td>
                                    @foreach($monthKeys as $mk)
                                        @php $v = $row->$mk ?? null; @endphp
                                        <td class="cf-td cf-td--amount {{ ($v !== null && (float)$v < 0) ? 'cf-td--withdrawal' : '' }}">{{ $v !== null ? number_format($v, 2, '.', ',') : '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="cf-tfoot">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Total') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount">{{ number_format($part1Totals[$idx + 1], 2, '.', ',') }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Part 2 --}}
            <div class="report-part-wrap mb-8">
                <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('Part 2') }} – {{ __('Nett') }}</h2>
                <div class="report-table-wrap overflow-x-auto">
                    <table class="cf-table report-table w-full">
                        <thead>
                            <tr>
                                <th class="cf-th" style="width:3rem;">{{ __('No') }}</th>
                                <th class="cf-th cf-td--left" style="min-width:140px;">{{ __('Description') }}</th>
                                @foreach($monthLabels as $m)
                                    <th class="cf-th" style="min-width:5rem;">{{ $m }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rowsBySection[2] as $i => $row)
                                <tr>
                                    <td class="cf-td">
                                        {{ $i + 1 }}
                                        <button type="button" class="all-years-row-del" title="{{ __('Delete row') }}" data-section="2" data-row-index="{{ $i }}">×</button>
                                    </td>
                                    <td class="cf-td cf-td--left">{{ $row->label }}</td>
                                    @foreach($monthKeys as $mk)
                                        @php $v = $row->$mk ?? null; @endphp
                                        <td class="cf-td cf-td--amount {{ ($v !== null && (float)$v < 0) ? 'cf-td--withdrawal' : '' }}">{{ $v !== null ? number_format($v, 2, '.', ',') : '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="cf-tfoot">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Pending Amount') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount">{{ number_format($part2Pending[$idx + 1], 2, '.', ',') }}</td>
                                @endforeach
                            </tr>
                            <tr class="cf-tfoot">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Total') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount">{{ number_format($part2Totals[$idx + 1], 2, '.', ',') }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Part 3 --}}
            <div class="report-part-wrap mb-8">
                <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('Part 3') }} – {{ __('Nett') }}</h2>
                <div class="report-table-wrap overflow-x-auto">
                    <table class="cf-table report-table w-full">
                        <thead>
                            <tr>
                                <th class="cf-th" style="width:4rem;">{{ __('No') }}</th>
                                <th class="cf-th cf-td--left" style="min-width:140px;">{{ __('Description') }}</th>
                                @foreach($monthLabels as $m)
                                    <th class="cf-th" style="min-width:5rem;">{{ $m }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rowsBySection[3] as $i => $row)
                                <tr>
                                    <td class="cf-td">
                                        {{ $i + 1 }}
                                        <button type="button" class="all-years-row-del" title="{{ __('Delete row') }}" data-section="3" data-row-index="{{ $i }}">×</button>
                                    </td>
                                    <td class="cf-td cf-td--left">{{ $row->label }}</td>
                                    @foreach($monthKeys as $mk)
                                        @php $v = $row->$mk ?? null; @endphp
                                        <td class="cf-td cf-td--amount {{ ($v !== null && (float)$v < 0) ? 'cf-td--withdrawal' : '' }}">{{ $v !== null ? number_format($v, 2, '.', ',') : '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="cf-tfoot">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Total') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount">{{ number_format($part3Totals[$idx + 1], 2, '.', ',') }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Part 4 --}}
            <div class="report-part-wrap mb-8">
                <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('Part 4') }} – {{ __('Open Capital') }}</h2>
                <div class="report-table-wrap overflow-x-auto">
                    <table class="cf-table report-table w-full">
                        <thead>
                            <tr>
                                <th class="cf-th" style="width:4rem;">{{ __('No') }}</th>
                                <th class="cf-th cf-td--left" style="min-width:140px;">{{ __('Description') }}</th>
                                @foreach($monthLabels as $m)
                                    <th class="cf-th" style="min-width:5rem;">{{ $m }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rowsBySection[4] as $i => $row)
                                <tr>
                                    <td class="cf-td">
                                        {{ $i + 1 }}
                                        <button type="button" class="all-years-row-del" title="{{ __('Delete row') }}" data-section="4" data-row-index="{{ $i }}">×</button>
                                    </td>
                                    <td class="cf-td cf-td--left">{{ $row->label }}</td>
                                    @foreach($monthKeys as $mk)
                                        @php $v = $row->$mk ?? null; @endphp
                                        <td class="cf-td cf-td--amount {{ ($v !== null && (float)$v < 0) ? 'cf-td--withdrawal' : '' }}">{{ $v !== null ? number_format($v, 2, '.', ',') : '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="cf-tfoot">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Total') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount">{{ number_format($part4Totals[$idx + 1], 2, '.', ',') }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.all-years-row-del').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var form = document.getElementById('all-years-delete-form');
                var msg = form.getAttribute('data-confirm');
                if (msg && !confirm(msg)) return;
                form.action = form.getAttribute('data-action');
                form.querySelector('input[name="section"]').value = this.getAttribute('data-section');
                form.querySelector('input[name="row_index"]').value = this.getAttribute('data-row-index');
                form.submit();
            });
        });
    </script>
</x-app-layout>
