<x-app-layout>
    <x-slot name="header">
        {{ __('Cashflow') }}
    </x-slot>
    @php
        $isSystem = $currentCompanyId === 0;
        $currentCompany = $isSystem ? null : $companies->firstWhere('id', $currentCompanyId);
        $numFixedCols = 7 + count($extraColumns);
        $numCols = $numFixedCols + (($canDelete ?? false) ? 1 : 0);
        $filters = $filters ?? ['date_from' => '', 'date_to' => '', 'date_all' => '0', 'remark' => ''];
    @endphp
    <style>
        .cf-page .cf-table.cf-table--gray .cf-th { background: #f9fafb !important; }
        .cf-page .cf-table .cf-td input.cf-input { width: 100%; max-width: 8rem; padding: 0.25rem 0.4rem; font-size: 0.875rem; border: 1px solid #e5e7eb; border-radius: 4px; }
        .cf-page.cf-edit-mode .cf-view-cell { display: none; }
        .cf-page.cf-edit-mode .cf-edit-cell { display: block; }
        .cf-page:not(.cf-edit-mode) .cf-edit-cell { display: none; }
        .cf-page:not(.cf-edit-mode) .cf-view-cell { display: block; }
        .cf-page .cf-td.cf-td--withdrawal { color: #dc2626; }
        .cf-page .cf-tfoot .cf-td { pointer-events: none; }
        .cf-page .cf-filter-field { width: 260px; max-width: 260px; }
        .cf-page .cf-top-select,
        .cf-page .cf-top-input { height: 2.5rem; }
        .cf-page .cf-top-select { padding-top: 0; padding-bottom: 0; }
        .cf-page .cf-top-input { padding-top: 0.25rem; padding-bottom: 0.25rem; }
        .cf-page .cf-filter-bar { margin-bottom: 38px; } /* ~1cm */
        .cf-page .report-table-wrap { -webkit-overflow-scrolling: touch; }
        .cf-page .cf-filter-control {
            height: 34px !important;
            min-height: 34px !important;
            box-sizing: border-box;
            line-height: 34px;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        .cf-page input.cf-filter-control { line-height: 34px; }
        .cf-page .drp-display-input {
            height: 34px !important;
            min-height: 34px !important;
            box-sizing: border-box;
            line-height: 34px !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        .cf-page .cf-filter-btn {
            height: 34px !important;
            min-height: 34px !important;
            display: inline-flex;
            align-items: center;
            box-sizing: border-box;
            line-height: 34px;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        /* Align Date Range block with other filter fields */
        .cf-page .cf-filter-field .form-group { display: flex; flex-direction: column; gap: 0.25rem; }
        .cf-page .cf-filter-field .form-group > label { margin-bottom: 0 !important; }
        /* Nudge Remark + buttons down to align visually with Date Range */
        .cf-page .cf-filter-nudge { margin-top: 6px; }
        /* 可拖曳的 row（類似 Excel 拖動整行） */
        .cf-row-draggable { cursor: move; }
        .cf-delete-row-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.25rem; height: 1.25rem; padding: 0; font-size: 1rem; line-height: 1;
            color: #b91c1c; background: transparent; border: 1px solid rgba(185,28,28,.4); border-radius: 4px;
            cursor: pointer;
        }
        .cf-delete-row-btn:hover { color: #fff; background: #b91c1c; }
        /* Delete 列仅在 Edit 模式显示 */
        .cf-page:not(.cf-edit-mode) .cf-delete-col { display: none !important; }
        .cf-page { min-width: 720px; }
        @media (max-width: 768px) {
            .cf-page { min-width: 0; width: 100%; }
            .cf-page .flex.flex-wrap.justify-between { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .cf-page .cf-filter-bar { flex-direction: column; align-items: stretch; }
            .cf-page .cf-filter-field { max-width: none; }
        }
    </style>
    <div class="max-w-7xl mx-auto w-full pb-12 cf-page" id="cf-page-wrap">
        @if(session('success'))
            <p class="text-sm text-green-700 mb-4">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="text-sm text-red-700 mb-4">{{ session('error') }}</p>
        @endif
        @if($errors->any())
            <div class="text-sm text-red-700 mb-4">
                @foreach($errors->all() as $err) <p>{{ $err }}</p> @endforeach
            </div>
        @endif

        @if($canDelete ?? false)
        <form id="cf-delete-form" method="post" action="" style="display:none;" data-confirm="{{ e(__('Delete this entry?')) }}">
            @csrf
            @method('DELETE')
        </form>
        @endif

        <div class="flex flex-wrap justify-between items-end gap-4 mb-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Cashflow') }}</div>
                @if($isSystem)
                    <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ __('Master Cashflow') }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ __('Add row or add column below.') }}</p>
                @else
                    <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ __('Company Cashflow') }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $currentCompany ? $currentCompany->name : '' }}. {{ __('Add row or add column below.') }}</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <form method="get" action="{{ route('cashflow.index') }}" class="flex gap-2 items-center">
                    <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                    <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                    <input type="hidden" name="date_all" value="{{ $filters['date_all'] ?? '0' }}">
                    <input type="hidden" name="remark" value="{{ $filters['remark'] ?? '' }}">
                    <label for="cf-mode" class="text-sm text-gray-600">{{ $isSystem ? __('Master') : __('Company') }}</label>
                    <select id="cf-mode" name="company_id" class="cf-top-select rounded border border-gray-300 px-2.5 text-sm w-48" onchange="this.form.submit()">
                        <option value="0" {{ $isSystem ? 'selected' : '' }}>{{ __('Master Cashflow') }}</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ $currentCompanyId === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </form>
                <button type="button" id="cf-edit-btn" class="user-btn user-btn--secondary" style="min-width: auto;">{{ __('Edit') }}</button>
                <span id="cf-edit-actions" style="display: none;">
                    <button type="button" id="cf-add-row" class="user-btn user-btn--primary" style="min-width: auto;">{{ __('+ Add row') }}</button>
                </span>
                <form method="post" action="{{ route('cashflow.columns.store') }}" class="inline flex gap-2 items-center">
                    @csrf
                    <input type="hidden" name="company_id" value="{{ $currentCompanyId }}">
                    <input type="text" name="name" placeholder="{{ __('Column name') }}" maxlength="64" class="cf-top-input rounded border border-gray-300 px-2 text-sm w-32" required>
                    <button type="submit" class="user-btn user-btn--secondary" style="min-width: auto;">{{ __('+ Add column') }}</button>
                </form>
                <a href="{{ route('cashflow.export', $currentCompanyId ? ['company_id' => $currentCompanyId] : []) }}" class="user-btn user-btn--secondary" style="min-width: auto;" target="_blank">
                    {{ __('Export') }}
                </a>
            </div>
        </div>

        <form method="get" action="{{ route('cashflow.index') }}" class="cf-filter-bar flex flex-wrap gap-4 items-end border-t border-gray-200 pt-5">
            <input type="hidden" name="company_id" value="{{ $currentCompanyId }}">
            <div class="cf-filter-field">
                <x-date-range-picker
                    :date-from="$filters['date_from']"
                    :date-to="$filters['date_to']"
                    :date-all="$filters['date_all'] ?? '0'"
                />
            </div>
            <div class="flex flex-col gap-1 cf-filter-field cf-filter-nudge">
                <label class="text-xs font-medium text-gray-500">{{ __('Remark') }}</label>
                <input type="text" name="remark" value="{{ $filters['remark'] ?? '' }}" placeholder="{{ __('Search by remark...') }}"
                       class="cf-filter-control rounded border border-gray-300 px-2.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-400 w-full">
            </div>
            <div class="flex flex-col gap-1 cf-filter-nudge">
                <label class="text-xs font-medium text-gray-500 opacity-0 select-none">{{ __('Remark') }}</label>
                <div class="flex gap-2 items-center">
                    <button type="submit" class="cf-filter-control cf-filter-btn rounded border border-gray-300 bg-gray-800 px-3 text-sm font-medium text-white hover:bg-gray-700">{{ __('Apply') }}</button>
                    <a href="{{ route('cashflow.index', ['company_id' => $currentCompanyId]) }}" class="cf-filter-control cf-filter-btn rounded border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 justify-center">{{ __('Reset') }}</a>
                </div>
            </div>
        </form>

        <form method="post" action="{{ route('cashflow.rows.store') }}" id="cf-form">
            @csrf
            <input type="hidden" name="company_id" value="{{ $currentCompanyId }}">
            <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <input type="hidden" name="date_all" value="{{ $filters['date_all'] ?? '0' }}">
            <input type="hidden" name="remark" value="{{ $filters['remark'] ?? '' }}">
            <input type="hidden" name="row_order" id="cf-row-order" value="">
            <input type="hidden" name="column_order" id="cf-col-order" value="">
            <div class="p-0">
                <div class="report-table-wrap overflow-x-auto">
                    <table class="cf-table report-table cf-table--gray w-full" style="min-width: 700px;">
                        <thead>
                            <tr>
                                <th class="cf-th cf-col-draggable" draggable="true" data-col-key="date">{{ __('Date') }}</th>
                                <th class="cf-th cf-col-draggable" draggable="true" data-col-key="deposit">{{ __('Deposit') }}</th>
                                <th class="cf-th cf-col-draggable" draggable="true" data-col-key="withdrawal">{{ __('Withdrawals') }}</th>
                                <th class="cf-th cf-col-draggable" draggable="true" data-col-key="affin">AFFIN</th>
                                <th class="cf-th cf-col-draggable" draggable="true" data-col-key="total">{{ __('Total') }}</th>
                                <th class="cf-th cf-col-draggable" draggable="true" data-col-key="xe_usdt">Xe USDT</th>
                                @foreach($extraColumns as $col)
                                    <th class="cf-th cf-col-draggable" draggable="true" data-col-key="extra:{{ $col->id }}">{{ $col->name }}</th>
                                @endforeach
                                <th class="cf-th cf-td--left cf-col-draggable" draggable="true" data-col-key="remark">{{ __('Remark') }}</th>
                                @if($canDelete ?? false)
                                <th class="cf-th cf-delete-col" style="width:2.5rem;">{{ __('Delete') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // If the current filtered result is empty, still show a bring-forward row for the selected month (based on date_from/date_to).
                                $emptyRangeMonthStart = null;
                                if ($entries->isEmpty()) {
                                    $seed = $filters['date_from'] ?: ($filters['date_to'] ?: '');
                                    if ($seed !== '') {
                                        try {
                                            $emptyRangeMonthStart = \Carbon\Carbon::parse($seed)->startOfMonth();
                                        } catch (\Throwable $ex) {
                                            $emptyRangeMonthStart = null;
                                        }
                                    }
                                }
                                if ($emptyRangeMonthStart) {
                                    $prevMonthKey = $emptyRangeMonthStart->copy()->subMonth()->format('Y-m');
                                    $bfTotalMinor = $monthlyClosing['total_minor'][$prevMonthKey] ?? null;
                                    $bfAffinMinor = $monthlyClosing['affin_minor'][$prevMonthKey] ?? null;
                                    $bfXeUsdtMinor = $monthlyClosing['xe_usdt_minor'][$prevMonthKey] ?? null;
                                    $bfExtraMinors = [];
                                    foreach ($extraColumns as $col) {
                                        $colId = $col->id;
                                        $bfExtraMinors[$colId] = $monthlyClosing['extra_minor'][$colId][$prevMonthKey] ?? null;
                                    }
                                    $bfLabel = $emptyRangeMonthStart->copy()->subMonth()->format('MY');
                                }
                            @endphp

                            @if($emptyRangeMonthStart && ($bfTotalMinor !== null || $bfAffinMinor !== null || $bfXeUsdtMinor !== null))
                                <tr class="cf-td-bf">
                                    <td class="cf-td">{{ $emptyRangeMonthStart->format('Y-m-d') }}</td>
                                    <td class="cf-td"></td>
                                    <td class="cf-td"></td>
                                    <td class="cf-td cf-td--amount {{ ($bfAffinMinor ?? 0) < 0 ? 'cf-td--withdrawal' : '' }}">
                                        {{ ($bfAffinMinor ?? 0) >= 0 ? number_format(($bfAffinMinor ?? 0) / 100, 2) : '(' . number_format(abs(($bfAffinMinor ?? 0)) / 100, 2) . ')' }}
                                    </td>
                                    <td class="cf-td cf-td--amount {{ ($bfTotalMinor ?? 0) < 0 ? 'cf-td--withdrawal' : '' }}">
                                        {{ ($bfTotalMinor ?? 0) >= 0 ? number_format(($bfTotalMinor ?? 0) / 100, 2) : '(' . number_format(abs(($bfTotalMinor ?? 0)) / 100, 2) . ')' }}
                                    </td>
                                    @php $bfXe = $bfXeUsdtMinor ?? 0; @endphp
                                    <td class="cf-td cf-td--amount {{ $bfXe < 0 ? 'cf-td--withdrawal' : '' }}">
                                        {{ $bfXe >= 0 ? number_format($bfXe / 100, 2) : '(' . number_format(abs($bfXe) / 100, 2) . ')' }}
                                    </td>
                                    @foreach($extraColumns as $col)
                                        @php $bfExtra = $bfExtraMinors[$col->id] ?? 0; @endphp
                                        <td class="cf-td cf-td--amount {{ $bfExtra < 0 ? 'cf-td--withdrawal' : '' }}">
                                            {{ $bfExtra >= 0 ? number_format($bfExtra / 100, 2) : '(' . number_format(abs($bfExtra) / 100, 2) . ')' }}
                                        </td>
                                    @endforeach
                                    <td class="cf-td cf-td--left">{{ __('Balance bring forward') }} {{ $bfLabel }}</td>
                                    @if($canDelete ?? false)<td class="cf-td cf-delete-col"></td>@endif
                                </tr>
                            @endif

                            @forelse($entries as $e)
                                @php
                                    $amt = $e->amount_minor / 100;
                                    $isIn = $e->amount_minor >= 0;
                                    $depositVal = $e->deposit_minor !== null ? ($e->deposit_minor / 100) : null;
                                    $withdrawalVal = $e->withdrawal_minor !== null ? ($e->withdrawal_minor / 100) : null;
                                    $affin = ($e->affin_minor ?? 0) / 100;
                                    $xeUsdt = (($e->xe_minor ?? 0) + ($e->usdt_minor ?? 0)) / 100;
                                    $monthKey = $e->entry_date->format('Y-m');
                                    $prevMonthKey = $e->entry_date->copy()->subMonth()->format('Y-m');
                                    $bfTotalMinor = $monthlyClosing['total_minor'][$prevMonthKey] ?? null;
                                    $bfAffinMinor = $monthlyClosing['affin_minor'][$prevMonthKey] ?? null;
                                    $bfXeUsdtMinor = $monthlyClosing['xe_usdt_minor'][$prevMonthKey] ?? null;
                                    $bfLabel = $e->entry_date->copy()->subMonth()->format('MY');
                                @endphp
                                @if(!isset($printedBfMonths)) @php $printedBfMonths = []; @endphp @endif
                                @if(!in_array($monthKey, $printedBfMonths, true) && ($bfTotalMinor !== null || $bfAffinMinor !== null || $bfXeUsdtMinor !== null))
                                    <tr class="cf-td-bf">
                                        <td class="cf-td">{{ $e->entry_date->copy()->startOfMonth()->format('Y-m-d') }}</td>
                                        <td class="cf-td"></td>
                                        <td class="cf-td"></td>
                                        <td class="cf-td cf-td--amount {{ ($bfAffinMinor ?? 0) < 0 ? 'cf-td--withdrawal' : '' }}">
                                            {{ ($bfAffinMinor ?? 0) >= 0 ? number_format(($bfAffinMinor ?? 0) / 100, 2) : '(' . number_format(abs(($bfAffinMinor ?? 0)) / 100, 2) . ')' }}
                                        </td>
                                        <td class="cf-td cf-td--amount {{ ($bfTotalMinor ?? 0) < 0 ? 'cf-td--withdrawal' : '' }}">
                                            {{ ($bfTotalMinor ?? 0) >= 0 ? number_format(($bfTotalMinor ?? 0) / 100, 2) : '(' . number_format(abs(($bfTotalMinor ?? 0)) / 100, 2) . ')' }}
                                        </td>
                                         @php $bfXe2 = $bfXeUsdtMinor ?? 0; @endphp
                                         <td class="cf-td cf-td--amount {{ $bfXe2 < 0 ? 'cf-td--withdrawal' : '' }}">
                                             {{ $bfXe2 >= 0 ? number_format($bfXe2 / 100, 2) : '(' . number_format(abs($bfXe2) / 100, 2) . ')' }}
                                        </td>
                                        @foreach($extraColumns as $col)
                                            @php
                                                $colId = $col->id;
                                                $bfExtra = $monthlyClosing['extra_minor'][$colId][$prevMonthKey] ?? 0;
                                            @endphp
                                            <td class="cf-td cf-td--amount {{ $bfExtra < 0 ? 'cf-td--withdrawal' : '' }}">
                                                {{ $bfExtra >= 0 ? number_format($bfExtra / 100, 2) : '(' . number_format(abs($bfExtra) / 100, 2) . ')' }}
                                            </td>
                                        @endforeach
                                        <td class="cf-td cf-td--left">{{ __('Balance bring forward') }} {{ $bfLabel }}</td>
                                        @if($canDelete ?? false)<td class="cf-td cf-delete-col"></td>@endif
                                    </tr>
                                    @php $printedBfMonths[] = $monthKey; @endphp
                                @endif
                                <tr data-entry-id="{{ $e->id }}" class="cf-row-draggable" draggable="true">
                                    <td class="cf-td">
                                        <span class="cf-view-cell">{{ $e->entry_date->format('Y-m-d') }}</span>
                                        <span class="cf-edit-cell"><input type="date" name="entries[{{ $e->id }}][entry_date]" class="cf-input" value="{{ $e->entry_date->format('Y-m-d') }}"></span>
                                    </td>
                                    <td class="cf-td cf-td--amount">
                                        <span class="cf-view-cell">{{ number_format($depositVal ?? 0, 2) }}</span>
                                        <span class="cf-edit-cell"><input type="number" name="entries[{{ $e->id }}][deposit]" class="cf-input cf-input-deposit" step="0.01" value="{{ $depositVal !== null ? $depositVal : 0 }}"></span>
                                    </td>
                                    <td class="cf-td cf-td--amount {{ !$isIn ? 'cf-td--withdrawal' : '' }}">
                                        <span class="cf-view-cell">
                                            {{ $withdrawalVal !== null && $withdrawalVal > 0 ? '(' . number_format($withdrawalVal, 2) . ')' : '0.00' }}
                                        </span>
                                        <span class="cf-edit-cell"><input type="number" name="entries[{{ $e->id }}][withdrawal]" class="cf-input cf-input-withdrawal" step="0.01" value="{{ $withdrawalVal !== null ? abs($withdrawalVal) : 0 }}"></span>
                                    </td>
                                    <td class="cf-td cf-td--amount {{ $affin < 0 ? 'cf-td--withdrawal' : '' }}">
                                        <span class="cf-view-cell">
                                            {{ $affin >= 0 ? number_format($affin, 2) : '(' . number_format(abs($affin), 2) . ')' }}
                                        </span>
                                        <span class="cf-edit-cell"><input type="number" name="entries[{{ $e->id }}][affin]" class="cf-input" step="0.01" value="{{ $affin }}"></span>
                                    </td>
                                    <td class="cf-td cf-td--amount {{ !$isIn ? 'cf-td--withdrawal' : '' }}">
                                        <span class="cf-view-cell">{{ $isIn ? number_format($amt, 2) : '(' . number_format(abs($amt), 2) . ')' }}</span>
                                        <span class="cf-edit-cell"><input type="number" name="entries[{{ $e->id }}][total]" class="cf-input cf-input-total" step="0.01" value="{{ $amt }}"></span>
                                    </td>
                                    <td class="cf-td cf-td--amount {{ $xeUsdt < 0 ? 'cf-td--withdrawal' : '' }}">
                                        <span class="cf-view-cell">
                                            {{ $xeUsdt >= 0 ? number_format($xeUsdt, 2) : '(' . number_format(abs($xeUsdt), 2) . ')' }}
                                        </span>
                                        <span class="cf-edit-cell"><input type="number" name="entries[{{ $e->id }}][xe_usdt]" class="cf-input" step="0.01" value="{{ $xeUsdt }}"></span>
                                    </td>
                                    @foreach($extraColumns as $col)
                                        <td class="cf-td cf-td--amount">
                                            <span class="cf-view-cell">{{ number_format($e->getExtraValueMinor($col->id) / 100, 2) }}</span>
                                            <span class="cf-edit-cell"><input type="number" name="entries[{{ $e->id }}][extra][{{ $col->id }}]" class="cf-input" step="0.01" value="{{ $e->getExtraValueMinor($col->id) / 100 }}"></span>
                                        </td>
                                    @endforeach
                                    <td class="cf-td cf-td--left">
                                        <span class="cf-view-cell">{{ Str::limit($e->description ?? '', 40) ?: '—' }}</span>
                                        <span class="cf-edit-cell"><input type="text" name="entries[{{ $e->id }}][description]" class="cf-input" value="{{ $e->description ?? '' }}" style="max-width: 12rem;"></span>
                                    </td>
                                    @if($canDelete ?? false)
                                    <td class="cf-td cf-delete-col">
                                        <button type="button" class="cf-delete-row-btn" title="{{ __('Delete row') }}" data-url="{{ route('cashflow.destroy', $e->id) }}">×</button>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $numCols }}" class="cf-td cf-td--left" style="padding: 2rem; color: #6b7280;">
                                        {{ __('No cashflow entries.') }} {{ __('Click + Add row to add a row.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        {{-- Template for new row (cloned by JS); disabled so not submitted/validated --}}
                        <tr id="cf-new-row-tpl" class="cf-new-row cf-row-draggable" style="display: none;" draggable="true">
                            <td class="cf-td"><input type="date" name="new_rows[0][entry_date]" class="cf-input" disabled></td>
                            <td class="cf-td"><input type="number" name="new_rows[0][deposit]" class="cf-input cf-input-deposit" step="0.01" placeholder="0" disabled></td>
                            <td class="cf-td"><input type="number" name="new_rows[0][withdrawal]" class="cf-input cf-input-withdrawal" step="0.01" placeholder="0" disabled></td>
                            <td class="cf-td"><input type="number" name="new_rows[0][affin]" class="cf-input" step="0.01" placeholder="0" disabled></td>
                            <td class="cf-td"><input type="number" name="new_rows[0][total]" class="cf-input cf-input-total" step="0.01" placeholder="0" disabled></td>
                            <td class="cf-td"><input type="number" name="new_rows[0][xe_usdt]" class="cf-input" step="0.01" placeholder="0" disabled></td>
                            @foreach($extraColumns as $col)
                                <td class="cf-td"><input type="number" name="new_rows[0][extra][{{ $col->id }}]" class="cf-input" step="0.01" placeholder="0" value="0" disabled></td>
                            @endforeach
                            <td class="cf-td"><input type="text" name="new_rows[0][description]" class="cf-input" placeholder="{{ __('Remark') }}" style="max-width: 12rem;" disabled></td>
                            <td class="cf-td cf-delete-col">
                                {{-- 這些只作用於尚未儲存的新列：上下移動與刪除，不觸發後端 --}}
                                <button type="button" class="cf-delete-row-btn cf-new-row-move-up" title="{{ __('Move up') }}" style="margin-right:2px;">↑</button>
                                <button type="button" class="cf-delete-row-btn cf-new-row-move-down" title="{{ __('Move down') }}" style="margin-right:2px;">↓</button>
                                <button type="button" class="cf-delete-row-btn cf-new-row-delete" title="{{ __('Delete row') }}">×</button>
                            </td>
                        </tr>
                        @if($entries->isNotEmpty())
                            @php
                                // Totals: Deposit/Withdrawal based on explicit columns (if present), not from Total column.
                                $useDepCols = \Illuminate\Support\Facades\Schema::hasColumn('cashflow_entries', 'deposit_minor')
                                    && \Illuminate\Support\Facades\Schema::hasColumn('cashflow_entries', 'withdrawal_minor');
                                if ($useDepCols) {
                                    $totalDepositMinor = $entries->sum(fn ($e) => $e->deposit_minor ?? 0);
                                    $totalWithdrawalMinor = $entries->sum(fn ($e) => $e->withdrawal_minor ?? 0);
                                } else {
                                    $totalDepositMinor = $entries->sum(fn ($e) => $e->amount_minor > 0 ? $e->amount_minor : 0);
                                    $totalWithdrawalMinor = $entries->sum(fn ($e) => $e->amount_minor < 0 ? abs($e->amount_minor) : 0);
                                }
                                $totalAffinMinor = $entries->sum(fn ($e) => $e->affin_minor ?? 0);
                                $totalXeUsdtMinor = $entries->sum(fn ($e) => ($e->xe_minor ?? 0) + ($e->usdt_minor ?? 0));
                                // Bottom "Total" column should match the sum of each row's Total column (amount_minor).
                                $netMinor = $entries->sum(fn ($e) => $e->amount_minor);
                                $net = $netMinor / 100;
                                $totalDeposit = $totalDepositMinor / 100;
                                $totalWithdrawal = $totalWithdrawalMinor / 100;
                                $totalAffin = $totalAffinMinor / 100;
                                $totalXeUsdt = $totalXeUsdtMinor / 100;
                            @endphp
                            <tfoot class="cf-tfoot">
                                <tr>
                                    <td class="cf-td">{{ __('Total') }}</td>
                                    <td class="cf-td cf-td--amount">{{ number_format($totalDeposit, 2) }}</td>
                                    <td class="cf-td cf-td--amount cf-td--withdrawal">({{ number_format($totalWithdrawal, 2) }})</td>
                                    <td class="cf-td cf-td--amount">{{ number_format($totalAffin, 2) }}</td>
                                    <td class="cf-td cf-td--amount {{ $net < 0 ? 'cf-td--withdrawal' : '' }}">{{ $net >= 0 ? number_format($net, 2) : '(' . number_format(abs($net), 2) . ')' }}</td>
                                    <td class="cf-td cf-td--amount {{ $totalXeUsdt < 0 ? 'cf-td--withdrawal' : '' }}">
                                        {{ $totalXeUsdt >= 0 ? number_format($totalXeUsdt, 2) : '(' . number_format(abs($totalXeUsdt), 2) . ')' }}
                                    </td>
                                    @foreach($extraColumns as $col)
                                        @php $colTotal = $entries->sum(fn ($e) => $e->getExtraValueMinor($col->id)); @endphp
                                        <td class="cf-td cf-td--amount">{{ number_format($colTotal / 100, 2) }}</td>
                                    @endforeach
                                    <td class="cf-td"></td>
                                    @if($canDelete ?? false)<td class="cf-td cf-delete-col"></td>@endif
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <div class="mt-3 flex gap-2 items-center" id="cf-save-wrap" style="display: none;">
                    <button type="submit" id="cf-save-rows" class="user-btn user-btn--primary" style="min-width: auto;">{{ __('Save') }}</button>
                </div>
            </div>
        </form>

        @if($entries->hasPages())
            <div class="mt-4 flex justify-center gap-2">
                @if($entries->onFirstPage())
                    <span class="text-gray-400 text-sm">{{ __('Previous') }}</span>
                @else
                    <a href="{{ $entries->previousPageUrl() }}" class="text-sm text-gray-700 hover:text-black">{{ __('Previous') }}</a>
                @endif
                <span class="text-sm text-gray-600">{{ $entries->currentPage() }} / {{ $entries->lastPage() }}</span>
                @if($entries->hasMorePages())
                    <a href="{{ $entries->nextPageUrl() }}" class="text-sm text-gray-700 hover:text-black">{{ __('Next') }}</a>
                @else
                    <span class="text-gray-400 text-sm">{{ __('Next') }}</span>
                @endif
            </div>
        @endif
    </div>

    <script>
    (function() {
        var pageWrap = document.getElementById('cf-page-wrap');
        var form = document.getElementById('cf-form');
        var table = form && form.querySelector('table.cf-table');
        var thead = table && table.querySelector('thead');
        var headerRow = thead && thead.querySelector('tr');
        var tbody = form && form.querySelector('table tbody');
        var tpl = document.getElementById('cf-new-row-tpl');
        var addBtn = document.getElementById('cf-add-row');
        var editBtn = document.getElementById('cf-edit-btn');
        var editActions = document.getElementById('cf-edit-actions');
        var saveWrap = document.getElementById('cf-save-wrap');
        var rowIndex = 0;
        var dragRow = null;
        var dragColFrom = null;
        if (!tbody || !tpl || !addBtn || !editBtn || !table || !headerRow) return;

        function cellIndex(cell) {
            return Array.prototype.indexOf.call(cell.parentNode.children, cell);
        }

        function moveColumn(from, to) {
            if (from === to || from < 0 || to < 0) return;
            table.querySelectorAll('tr').forEach(function(row) {
                var firstCell = row.firstElementChild;
                if (firstCell && firstCell.hasAttribute('colspan')) return;
                if (!row.children) return;
                var cells = row.children;
                if (!cells[from] || !cells[to]) return;
                var moving = cells[from];
                if (to > from) {
                    row.insertBefore(moving, cells[to].nextSibling);
                } else {
                    row.insertBefore(moving, cells[to]);
                }
            });
        }

        function currentColumnKeys() {
            var keys = [];
            headerRow.querySelectorAll('th.cf-col-draggable').forEach(function(th) {
                keys.push(th.getAttribute('data-col-key') || '');
            });
            return keys.filter(Boolean);
        }

        // Apply persisted column order on load
        var initialColumnOrder = @json($columnOrder ?? []);
        if (Array.isArray(initialColumnOrder) && initialColumnOrder.length) {
            initialColumnOrder.forEach(function(key, targetIndex) {
                var th = headerRow.querySelector('th.cf-col-draggable[data-col-key="' + key.replace(/"/g, '\\"') + '"]');
                if (!th) return;
                var from = cellIndex(th);
                // After moving, indices change; move this key to targetIndex iteratively
                moveColumn(from, targetIndex);
            });
        }

        // Decide default date for new rows:
        // - If user选择了 date range，用 date_from（否则 date_to）那个月
        // - 否则，用今天
        function defaultDate() {
            var df = (form && form.querySelector('input[name="date_from"]') ? form.querySelector('input[name="date_from"]').value : '') || '';
            var dt = (form && form.querySelector('input[name="date_to"]') ? form.querySelector('input[name="date_to"]').value : '') || '';
            var baseStr = df || dt;
            var d;
            if (baseStr && /^\d{4}-\d{2}-\d{2}$/.test(baseStr)) {
                d = new Date(baseStr + 'T00:00:00');
            } else {
                d = new Date();
            }
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function updateTotalFromDepW(row) {
            var dep = parseFloat(row.querySelector('.cf-input-deposit') && row.querySelector('.cf-input-deposit').value) || 0;
            var wd = parseFloat(row.querySelector('.cf-input-withdrawal') && row.querySelector('.cf-input-withdrawal').value) || 0;
            var totalInput = row.querySelector('input.cf-input-total') || row.querySelector('input[name*="[total]"]');
            if (!totalInput) return;
            // If user manually keyed in total, don't auto-overwrite it.
            if (totalInput.dataset && totalInput.dataset.manual === '1') return;
            // Only auto-fill when total is empty (or explicitly not manual).
            if (String(totalInput.value || '').trim() !== '') return;
            totalInput.value = (dep - wd).toFixed(2);
        }

        function onTotalManual(row, totalInput) {
            if (!totalInput) return;
            var hasVal = String(totalInput.value || '').trim() !== '';
            totalInput.dataset.manual = hasVal ? '1' : '0';
            // When user keys in Total directly, keep Deposit/Withdrawal empty (Total is independent).
            if (hasVal) {
                var dep = row.querySelector('.cf-input-deposit');
                var wd = row.querySelector('.cf-input-withdrawal');
                if (dep) dep.value = '';
                if (wd) wd.value = '';
            }
        }

        // Header 拖動：調整整個 column 的顯示順序（僅前端，且只有在 Edit 模式）
        headerRow.addEventListener('dragstart', function(ev) {
            if (!pageWrap || !pageWrap.classList.contains('cf-edit-mode')) return;
            var th = ev.target.closest('th.cf-col-draggable');
            if (!th) return;
            dragColFrom = cellIndex(th);
            ev.dataTransfer.effectAllowed = 'move';
        });
        headerRow.addEventListener('dragover', function(ev) {
            if (dragColFrom === null) return;
            var th = ev.target.closest('th.cf-col-draggable');
            if (!th) return;
            ev.preventDefault();
        });
        headerRow.addEventListener('drop', function(ev) {
            if (dragColFrom === null) return;
            var thTo = ev.target.closest('th.cf-col-draggable');
            if (!thTo) return;
            ev.preventDefault();
            var to = cellIndex(thTo);
            var from = dragColFrom;
            dragColFrom = null;
            if (from === to || from < 0 || to < 0) return;

            moveColumn(from, to);
        });
        headerRow.addEventListener('dragend', function() { dragColFrom = null; });

        editBtn.addEventListener('click', function() {
            var isEdit = pageWrap && pageWrap.classList.contains('cf-edit-mode');
            if (isEdit) {
                pageWrap.classList.remove('cf-edit-mode');
                if (editActions) editActions.style.display = 'none';
                if (saveWrap) saveWrap.style.display = 'none';
                editBtn.textContent = '{{ __("Edit") }}';
            } else {
                pageWrap.classList.add('cf-edit-mode');
                if (editActions) editActions.style.display = 'inline';
                if (saveWrap) saveWrap.style.display = 'flex';
                editBtn.textContent = '{{ __("Cancel") }}';
            }
        });

        addBtn.addEventListener('click', function() {
            var placeholder = tbody.querySelector('tr td[colspan]');
            if (placeholder) placeholder.closest('tr').remove();
            rowIndex++;
            var clone = tpl.cloneNode(true);
            clone.id = '';
            clone.style.display = '';
            clone.classList.add('cf-new-row');
            [].forEach.call(clone.querySelectorAll('input'), function(inp) { inp.removeAttribute('disabled'); });
            clone.querySelector('input[name="new_rows[0][entry_date]"]').setAttribute('value', defaultDate());
            clone.querySelector('.cf-input-deposit').value = '';
            clone.querySelector('.cf-input-withdrawal').value = '';
            var totalInp = clone.querySelector('input[name*="[total]"]');
            if (totalInp) totalInp.value = '0';
            var html = clone.innerHTML.replace(/new_rows\[0\]/g, 'new_rows[' + rowIndex + ']');
            clone.innerHTML = html;
            var dateInp = clone.querySelector('input[name="new_rows[' + rowIndex + '][entry_date]"]');
            if (dateInp) { dateInp.setAttribute('value', defaultDate()); dateInp.setAttribute('required', 'required'); }
            var dep = clone.querySelector('.cf-input-deposit');
            var wd = clone.querySelector('.cf-input-withdrawal');
            var tot = clone.querySelector('.cf-input-total');
            if (dep) dep.addEventListener('input', function() { updateTotalFromDepW(clone); });
            if (wd) wd.addEventListener('input', function() { updateTotalFromDepW(clone); });
            if (tot) tot.addEventListener('input', function() { onTotalManual(clone, tot); });
            tbody.appendChild(clone);
        });

        tbody.addEventListener('input', function(ev) {
            var row = ev.target.closest('tr[data-entry-id]');
            if (row && (ev.target.classList.contains('cf-input-deposit') || ev.target.classList.contains('cf-input-withdrawal'))) {
                updateTotalFromDepW(row);
            }
            if (row && ev.target.classList.contains('cf-input-total')) {
                onTotalManual(row, ev.target);
            }
        });

        // 新增列的「移動 / 刪除」在前端完成，不影響已存在的資料
        tbody.addEventListener('click', function(ev) {
            var target = ev.target;
            if (!target.closest) return;
            var row = target.closest('tr.cf-new-row');
            if (!row) return;

            if (target.classList.contains('cf-new-row-delete')) {
                row.parentNode.removeChild(row);
                return;
            }
            if (target.classList.contains('cf-new-row-move-up')) {
                var prev = row.previousElementSibling;
                // 跳過模板行
                while (prev && prev.id === 'cf-new-row-tpl') {
                    prev = prev.previousElementSibling;
                }
                if (prev) {
                    row.parentNode.insertBefore(row, prev);
                }
                return;
            }
            if (target.classList.contains('cf-new-row-move-down')) {
                var next = row.nextElementSibling;
                // 跳過模板行
                while (next && next.id === 'cf-new-row-tpl') {
                    next = next.nextElementSibling;
                }
                if (next) {
                    row.parentNode.insertBefore(next, row);
                }
                return;
            }
        });

        // 整行拖拽（類似 Excel 拖動 row），僅改前端順序，且只有在 Edit 模式
        tbody.addEventListener('dragstart', function(ev) {
            if (!pageWrap || !pageWrap.classList.contains('cf-edit-mode')) return;
            var row = ev.target.closest('tr.cf-row-draggable');
            if (!row) return;
            dragRow = row;
            ev.dataTransfer.effectAllowed = 'move';
        });
        tbody.addEventListener('dragover', function(ev) {
            if (!dragRow) return;
            var targetRow = ev.target.closest('tr.cf-row-draggable');
            if (!targetRow || targetRow === dragRow) return;
            ev.preventDefault();
        });
        tbody.addEventListener('drop', function(ev) {
            if (!dragRow) return;
            var targetRow = ev.target.closest('tr.cf-row-draggable');
            if (!targetRow || targetRow === dragRow) return;
            ev.preventDefault();
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr.cf-row-draggable'));
            var from = rows.indexOf(dragRow);
            var to = rows.indexOf(targetRow);
            if (from === -1 || to === -1) return;
            if (from < to) {
                tbody.insertBefore(dragRow, targetRow.nextSibling);
            } else {
                tbody.insertBefore(dragRow, targetRow);
            }
            dragRow = null;
        });
        tbody.addEventListener('dragend', function() { dragRow = null; });

        // Submit: persist row_order + column_order together
        form.addEventListener('submit', function() {
            // Row order tokens
            var tokens = [];
            tbody.querySelectorAll('tr.cf-row-draggable').forEach(function(row) {
                var id = row.getAttribute('data-entry-id');
                if (id) {
                    tokens.push('e:' + id);
                } else {
                    var anyInput = row.querySelector('input[name^="new_rows["]');
                    if (!anyInput) return;
                    var m = anyInput.name.match(/^new_rows\[(\d+)\]/);
                    if (!m) return;
                    tokens.push('n:' + m[1]);
                }
            });
            var rowOrderInput = document.getElementById('cf-row-order');
            if (rowOrderInput) rowOrderInput.value = tokens.join(',');

            // Column order keys (excluding Delete)
            var colOrderInput = document.getElementById('cf-col-order');
            if (colOrderInput) colOrderInput.value = JSON.stringify(currentColumnKeys());
        });
    })();
    (function() {
        var deleteForm = document.getElementById('cf-delete-form');
        if (!deleteForm) return;
        var confirmMsg = deleteForm.getAttribute('data-confirm') || '{{ __("Delete this entry?") }}';
        document.querySelectorAll('.cf-delete-row-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm(confirmMsg)) return;
                deleteForm.action = this.getAttribute('data-url');
                deleteForm.submit();
            });
        });
    })();
    </script>
</x-app-layout>
