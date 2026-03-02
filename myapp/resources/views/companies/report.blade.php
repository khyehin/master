<x-app-layout>
    <x-slot name="header">
        {{ $company->name }} – {{ __('Transactions') }}
    </x-slot>
    <div class="report-page-root max-w-full mx-auto w-full pb-12">
        <div class="report-scroll-box">
        @if(session('success'))
            <p class="text-sm text-green-700 mb-4">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="text-sm text-red-700 mb-4">{{ session('error') }}</p>
        @endif

        <div class="report-header-row mb-6">
            <div>
                <a href="{{ route('companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">← {{ __('Companies') }}</a>
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Transactions') }}</div>
                <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ $company->name }} – {{ __('Monthly report') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('Year / month. Part 1: expenses. Part 2: Nett (Pending, Total). Part 3: Nett. Part 4: Open capital.') }}</p>
            </div>
            <div class="report-actions-top">
                <form method="get" action="{{ route('companies.report', $company->id) }}" class="flex items-center gap-2">
                    <label for="year" class="text-sm text-gray-600">{{ __('Year') }}</label>
                    <select id="year" name="year" class="rounded border border-gray-300 px-2.5 py-2 text-sm" onchange="this.form.submit()">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('companies.report.all-years', $company->id) }}" class="user-new-cell">
                    <span class="user-new-link">{{ __('Total all years') }}</span>
                </a>
                <a href="{{ route('cashflow.index', ['company_id' => $company->id]) }}" class="user-new-cell">
                    <span class="user-new-link">{{ __('Cashflow') }}</span>
                </a>
                <a href="{{ route('companies.report.export', ['id' => $company->id, 'year' => $year]) }}" class="user-new-cell" target="_blank">
                    <span class="user-new-link">{{ __('Export') }}</span>
                </a>
                <a href="{{ route('companies.report', ['id' => $company->id, 'year' => $year, 'add_section' => 1]) }}" class="user-new-cell">
                    <span class="user-new-link">+ {{ __('Add section') }}</span>
                </a>
                <button type="button" id="btn-edit" class="user-btn user-btn--secondary" style="min-width:auto;" onclick="setEditMode(true)">{{ __('Edit') }}</button>
            </div>
        </div>

        <form method="post" action="{{ route('companies.report.store', $company->id) }}" class="report-form-width-fix" id="report-form">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">

            @php
                $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                $monthKeys = \App\Models\CompanyReportRow::monthKeys();
                $sectionNumbers = $sectionNumbers ?? [1,2,3,4];
                $sectionTitles = $sectionTitles ?? collect();
                $defaultTitles = $defaultTitles ?? [1=>__('Part 1'),2=>__('Part 2'),3=>__('Part 3'),4=>__('Part 4')];
            @endphp

            {{-- Part 1 --}}
            <div class="report-part-wrap" data-section-block="1">
                <div class="content-layer p-4 report-part">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h2 class="text-base font-semibold text-gray-900">
                            <span class="section-title-view">{{ $sectionTitles->get(1)?->title ?? ($defaultTitles[1] ?? 'Part 1') }}</span>
                            <input type="text" class="section-title-edit report-field" name="section_1_title" value="{{ $sectionTitles->get(1)?->title ?? '' }}" placeholder="{{ $defaultTitles[1] ?? 'Part 1' }}" style="display:none; max-width:12rem;" disabled />
                            – {{ __('Year/Month') }} {{ $year }}
                        </h2>
                        <a href="{{ route('companies.report.delete-section', ['id' => $company->id, 'year' => $year, 'section' => 1]) }}" class="text-xs text-red-600 hover:text-red-800" onclick="return confirm('{{ __('Delete this section and all its data?') }}');">{{ __('Delete section') }}</a>
                    </div>
                    <div class="report-table-wrap">
                        <table class="cf-table report-table w-full">
                        <thead>
                            <tr>
                                <th class="cf-th" style="width:3rem;">{{ __('No') }}</th>
                                <th class="cf-th cf-td--left" style="min-width:140px;">{{ __('Description') }}</th>
                                @foreach($monthLabels as $m)
                                    <th class="cf-th" style="min-width:5rem;">{{ $m }}</th>
                                @endforeach
                                <th class="cf-th" style="min-width:5rem;">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody id="section-1-body">
                            @foreach($rowsBySection[1] as $i => $row)
                                @php $rowTotal1 = 0; foreach($monthKeys as $mk) { $rowTotal1 += (float)($row->$mk ?? 0); } @endphp
                                <tr>
                                    <td class="cf-td">
                                        <span class="row-no">{{ $i + 1 }}</span>
                                        <button type="button" class="row-del" onclick="deleteReportRow(this)" title="{{ __('Delete row') }}">×</button>
                                    </td>
                                    <td class="cf-td cf-td--left report-desc-cell">
                                        <div class="label-view">{{ $row->label }}</div>
                                        <textarea name="section_1_rows[{{ $i }}][label]" class="report-label label-edit w-full report-field" placeholder="{{ __('Description') }}" disabled style="display:none;">{{ $row->label }}</textarea>
                                    </td>
                                    @foreach($monthKeys as $mk)
                                        @php $v = $row->$mk; @endphp
                                        @php $display = $v !== null ? number_format($v, 2, '.', ',') : ''; @endphp
                                        <td class="cf-td report-amount-cell">
                                            <span class="amount-view {{ ($v !== null && (float)$v < 0) ? 'neg' : '' }}">{{ $display }}</span>
                                            <input type="number" step="0.01" name="section_1_rows[{{ $i }}][{{ $mk }}]" value="{{ $v !== null ? $v : '' }}" class="report-input report-amount report-field {{ ($v !== null && (float)$v < 0) ? 'neg' : '' }}" disabled style="display:none;">
                                        </td>
                                    @endforeach
                                    <td class="cf-td cf-td--amount row-total-cell">{{ number_format($rowTotal1, 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                            <tr class="cf-tfoot" id="section-1-total-row">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Total') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount" data-part1-total-m="{{ $idx + 1 }}">0</td>
                                @endforeach
                                <td class="cf-td cf-td--amount font-semibold" data-part1-total-col>0</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <button type="button" class="mt-2 text-sm text-gray-600 hover:text-gray-900 report-add-row" data-section="1" onclick="setEditMode(true); addReportRow(1);">+ {{ __('Add row') }}</button>
                </div>
            </div>

            {{-- Part 2 --}}
            <div class="report-part-wrap" data-section-block="2">
                <div class="content-layer p-4 report-part">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h2 class="text-base font-semibold text-gray-900">
                            <span class="section-title-view">{{ $sectionTitles->get(2)?->title ?? ($defaultTitles[2] ?? 'Part 2') }}</span>
                            <input type="text" class="section-title-edit report-field" name="section_2_title" value="{{ $sectionTitles->get(2)?->title ?? '' }}" placeholder="{{ $defaultTitles[2] ?? 'Part 2' }}" style="display:none; max-width:12rem;" disabled />
                            – {{ __('Nett') }} {{ $year }}
                        </h2>
                        <a href="{{ route('companies.report.delete-section', ['id' => $company->id, 'year' => $year, 'section' => 2]) }}" class="text-xs text-red-600 hover:text-red-800" onclick="return confirm('{{ __('Delete this section and all its data?') }}');">{{ __('Delete section') }}</a>
                    </div>
                    <div class="report-table-wrap">
                    <table class="cf-table report-table w-full">
                        <thead>
                            <tr>
                                <th class="cf-th" style="width:3rem;">{{ __('No') }}</th>
                                <th class="cf-th cf-td--left" style="min-width:140px;">{{ __('Description') }}</th>
                                @foreach($monthLabels as $m)
                                    <th class="cf-th" style="min-width:5rem;">{{ $m }}</th>
                                @endforeach
                                <th class="cf-th" style="min-width:5rem;">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody id="section-2-body">
                            @foreach($rowsBySection[2] as $i => $row)
                                @php $rowTotal2 = 0; foreach($monthKeys as $mk) { $rowTotal2 += (float)($row->$mk ?? 0); } @endphp
                                <tr>
                                    <td class="cf-td">
                                        <span class="row-no">{{ $i + 1 }}</span>
                                        <button type="button" class="row-del" onclick="deleteReportRow(this)" title="{{ __('Delete row') }}">×</button>
                                    </td>
                                    <td class="cf-td cf-td--left report-desc-cell">
                                        <div class="label-view">{{ $row->label }}</div>
                                        <textarea name="section_2_rows[{{ $i }}][label]" class="report-label label-edit w-full report-field" disabled style="display:none;">{{ $row->label }}</textarea>
                                    </td>
                                    @foreach($monthKeys as $mk)
                                        @php $v = $row->$mk; @endphp
                                        @php $display = $v !== null ? number_format($v, 2, '.', ',') : ''; @endphp
                                        <td class="cf-td report-amount-cell">
                                            <span class="amount-view {{ ($v !== null && (float)$v < 0) ? 'neg' : '' }}">{{ $display }}</span>
                                            <input type="number" step="0.01" name="section_2_rows[{{ $i }}][{{ $mk }}]" value="{{ $v !== null ? $v : '' }}" class="report-input report-amount section2-amount report-field {{ ($v !== null && (float)$v < 0) ? 'neg' : '' }}" disabled style="display:none;">
                                        </td>
                                    @endforeach
                                    <td class="cf-td cf-td--amount row-total-cell">{{ number_format($rowTotal2, 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                            <tr class="cf-tfoot" id="section-2-pending-row">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Pending Amount') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount" data-pending-m="{{ $idx + 1 }}">0</td>
                                @endforeach
                                <td class="cf-td cf-td--amount font-semibold" data-pending-col>0</td>
                            </tr>
                            <tr class="cf-tfoot" id="section-2-total-row">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Total') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount" data-part2-total-m="{{ $idx + 1 }}">0</td>
                                @endforeach
                                <td class="cf-td cf-td--amount font-semibold" data-part2-total-col>0</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <button type="button" class="mt-2 text-sm text-gray-600 hover:text-gray-900 report-add-row" data-section="2" onclick="setEditMode(true); addReportRow(2);">+ {{ __('Add row') }}</button>
                </div>
            </div>

            {{-- Part 3, 4, 5, ... (loop) --}}
            @foreach(array_filter($sectionNumbers, fn($s) => $s >= 3) as $sec)
            <div class="report-part-wrap" data-section-block="{{ $sec }}">
                <div class="content-layer p-4 report-part">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h2 class="text-base font-semibold text-gray-900">
                            <span class="section-title-view">{{ $sectionTitles->get($sec)?->title ?? ($defaultTitles[$sec] ?? ('Part ' . $sec)) }}</span>
                            <input type="text" class="section-title-edit report-field" name="section_{{ $sec }}_title" value="{{ $sectionTitles->get($sec)?->title ?? '' }}" placeholder="{{ $defaultTitles[$sec] ?? ('Part ' . $sec) }}" style="display:none; max-width:12rem;" disabled />
                            – {{ $year }}
                        </h2>
                        <a href="{{ route('companies.report.delete-section', ['id' => $company->id, 'year' => $year, 'section' => $sec]) }}" class="text-xs text-red-600 hover:text-red-800" onclick="return confirm('{{ __('Delete this section and all its data?') }}');">{{ __('Delete section') }}</a>
                    </div>
                    <div class="report-table-wrap">
                    <table class="cf-table report-table w-full">
                        <thead>
                            <tr>
                                <th class="cf-th" style="width:3rem;">{{ __('No') }}</th>
                                <th class="cf-th cf-td--left" style="min-width:140px;">{{ __('Description') }}</th>
                                @foreach($monthLabels as $m)
                                    <th class="cf-th" style="min-width:5rem;">{{ $m }}</th>
                                @endforeach
                                <th class="cf-th" style="min-width:5rem;">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody id="section-{{ $sec }}-body">
                            @foreach($rowsBySection[$sec] ?? [] as $i => $row)
                                @php $rowTotalSec = 0; foreach($monthKeys as $mk) { $rowTotalSec += (float)($row->$mk ?? 0); } @endphp
                                <tr>
                                    <td class="cf-td">
                                        <span class="row-no">{{ $i + 1 }}</span>
                                        <button type="button" class="row-del" onclick="deleteReportRow(this)" title="{{ __('Delete row') }}">×</button>
                                    </td>
                                    <td class="cf-td cf-td--left report-desc-cell">
                                        <div class="label-view">{{ $row->label }}</div>
                                        <textarea name="section_{{ $sec }}_rows[{{ $i }}][label]" class="report-label label-edit w-full report-field" disabled style="display:none;">{{ $row->label }}</textarea>
                                    </td>
                                    @foreach($monthKeys as $mk)
                                        @php $v = $row->$mk; @endphp
                                        @php $display = $v !== null ? number_format($v, 2, '.', ',') : ''; @endphp
                                        <td class="cf-td report-amount-cell">
                                            <span class="amount-view {{ ($v !== null && (float)$v < 0) ? 'neg' : '' }}">{{ $display }}</span>
                                            <input type="number" step="0.01" name="section_{{ $sec }}_rows[{{ $i }}][{{ $mk }}]" value="{{ $v !== null ? $v : '' }}" class="report-input report-amount report-field {{ ($v !== null && (float)$v < 0) ? 'neg' : '' }}" disabled style="display:none;">
                                        </td>
                                    @endforeach
                                    <td class="cf-td cf-td--amount row-total-cell">{{ number_format($rowTotalSec, 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                            <tr class="cf-tfoot" id="section-{{ $sec }}-total-row">
                                <td class="cf-td"></td>
                                <td class="cf-td cf-td--left font-semibold">{{ __('Total') }}</td>
                                @foreach($monthKeys as $idx => $mk)
                                    <td class="cf-td cf-td--amount" data-section-total-m="{{ $sec }}-{{ $idx + 1 }}">0</td>
                                @endforeach
                                <td class="cf-td cf-td--amount font-semibold" data-section-total-col="{{ $sec }}">0</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <div class="mt-1 text-sm text-gray-700 text-right">
                        {{ __('Year total') }}: <span data-section-year-total="{{ $sec }}">0</span>
                    </div>
                    <button type="button" class="mt-2 text-sm text-gray-600 hover:text-gray-900 report-add-row" data-section="{{ $sec }}" onclick="setEditMode(true); addReportRow({{ $sec }});">+ {{ __('Add row') }}</button>
                </div>
            </div>
            @endforeach

            <div class="flex gap-3 report-actions">
                <button type="submit" class="user-btn user-btn--primary" id="btn-save" style="display:none;">{{ __('Save') }}</button>
                <button type="button" class="user-btn user-btn--secondary" id="btn-cancel-edit" style="display:none;" onclick="cancelReportEdit();">{{ __('Cancel') }}</button>
            </div>
        </form>
        </div>{{-- /.report-scroll-box 整页可左右、上下滑动 --}}
    </div>

    <style>
        /* =========================
           REPORT PAGE – FULL RESPONSIVE (完整版)
           目标：
           1) 整页正常上下滚 (看完整页面)
           2) 只有表格区域出现左右滚动条
           3) Header 按钮自动换行，不撑破
           ========================= */
        
        /* ===== 大屏：min-width 触发 content-layer 左右滚；小屏：100% 宽，全部往下排，表内左右滚 ===== */
        .report-page-root{
          width:100%;
          max-width:100%;
          min-width:720px;
          box-sizing:border-box;
        }
        .report-page-root *{ box-sizing:border-box; }
        #report-form.report-form-width-fix{
          max-width:100%;
          min-width:0;
          width:100%;
        }
        
        /* ===== 顶部：标题 + 按钮，屏幕不够自动换行 ===== */
        .report-page-root .report-header-row{
          display:flex;
          align-items:flex-end;
          justify-content:space-between;
          gap:1rem;
          flex-wrap:wrap;
        }
        .report-page-root .report-header-row .report-actions-top{
          display:flex;
          align-items:center;
          gap:.75rem;
          flex-wrap:wrap;
        }
        .report-page-root .report-header-row .report-actions-top form{
          display:inline-flex;
          align-items:center;
          gap:.5rem;
          flex-wrap:wrap;
        }
        
        /* ===== 每个 Part 容器 ===== */
        .report-part-wrap{
          width:100%;
          max-width:100%;
          min-width:0;
          margin-bottom:1.5rem;
        }
        .report-part-wrap:last-of-type{ margin-bottom:0; }
        
        .report-part{
          width:100%;
          max-width:100%;
          min-width:0;
          overflow:visible;
          margin-bottom:2.5rem;
        }
        
        .report-scroll-box{
          width:100%;
          max-width:100%;
          min-width:0;
          max-height:none;
          border:1px solid transparent;
          border-radius:14px;
          background:transparent;
        }
        .report-table-wrap{
          width:100%;
          min-width:0;
          padding-bottom:10px;
          overflow-x:auto;
          -webkit-overflow-scrolling:touch;
        }
        @media (max-width: 768px) {
          .report-page-root{ min-width:0; width:100%; }
          .report-page-root .report-header-row{ flex-direction:column; align-items:flex-start; gap:.75rem; }
          .report-page-root .report-actions-top{ width:100%; justify-content:flex-start; }
        }
        
        /* ===== 表格：超出时在 report-table-wrap 横向滚 ===== */
        .report-table{
          font-size:.8rem;
          border:none;
          border-collapse:separate !important;
          border-spacing:0;
        
          /* ✅ 重点：按内容展开，但至少铺满容器 */
          width:max-content;
          min-width:100%;
        
          table-layout:fixed;
        }
        
        /* 格子样式 */
        .report-table .cf-th,
        .report-table .cf-td{
          border-radius:8px;
          overflow:hidden;
          box-sizing:border-box;
        }
        .report-table .cf-th{
          padding:.35rem .4rem !important;
          background:transparent;
          border:none !important;
          font-weight:600;
        }
        .report-table .cf-td{
          padding:.2rem .25rem !important;
          background:transparent;
          border-top:1px solid rgba(209,213,219,.5);
          border-bottom:1px solid rgba(209,213,219,.5);
          border-left:none;
          border-right:none;
          vertical-align:top;
        }
        .report-table tbody tr:first-child .cf-td{
          border-top:1px solid rgba(209,213,219,.6);
        }
        .report-table .cf-tfoot .cf-td{
          font-weight:600;
          border-top:1px solid rgba(156,163,175,.7);
        }
        
        /* 列宽：table-layout: fixed 下第一行决定列宽 */
        .report-table thead th:first-child{ width:3rem !important; }
        .report-table thead th:nth-child(2){ width:140px !important; }
        .report-table thead th:nth-child(n+3){ width:5rem !important; }
        
        /* 描述列：内容换行不撑破 */
        .report-table .cf-td--left,
        .report-table .report-desc-cell{
          overflow-wrap:anywhere;
          word-break:break-word;
          white-space:normal;
        }
        .report-table .report-desc-cell .label-view,
        .report-table .report-desc-cell .report-label{
          max-width:100%;
          overflow:hidden;
        }
        
        /* 金额列：单行省略号 */
        .report-table .report-amount-cell,
        .report-table .cf-td--amount{
          white-space:nowrap;
          overflow:hidden;
          text-overflow:ellipsis;
          text-align:right;
        }
        .report-table .report-amount-cell .amount-view,
        .report-table .report-amount-cell .report-input{
          max-width:100%;
          overflow:hidden;
          text-overflow:ellipsis;
          display:block;
        }
        
        /* Edit mode: 显示完整格子、间距 */
        body.is-editing .report-table{ border-spacing:5px 5px; }
        body.is-editing .report-table .cf-th,
        body.is-editing .report-table .cf-td{ border:1px solid #e5e7eb !important; }
        body.is-editing .report-table .cf-th{ background:#f9fafb !important; }
        body.is-editing .report-table .cf-td{ background:#fff; }
        body.is-editing .report-table .cf-tfoot .cf-td{ background:#f9fafb !important; }
        
        /* 输入框 */
        .report-input{
          width:100%;
          max-width:4.5rem;
          padding:.1rem .3rem;
          border:1px solid #e5e7eb;
          border-radius:6px;
          font-size:.75rem;
          text-align:right;
          box-sizing:border-box;
          display:block;
        }
        .report-input.report-amount{ max-width:100%; min-width:0; }
        .report-table .cf-td input.report-amount{ min-width:0; width:100%; }
        
        .amount-view{
          font-variant-numeric:tabular-nums;
          text-align:right;
          max-width:100%;
        }
        
        .report-desc-cell{ position:relative; }
        .label-view{
          font-size:.8rem;
          line-height:1.2;
          white-space:pre-wrap;
          overflow-wrap:anywhere;
          word-break:break-word;
        }
        .report-label{
          width:100%;
          max-width:100%;
          min-width:0;
          padding:.2rem .35rem;
          border:1px solid #e5e7eb;
          border-radius:6px;
          font-size:.78rem;
          line-height:1.2;
          text-align:left;
          resize:vertical;
          white-space:pre-wrap;
          overflow-wrap:anywhere;
          min-height:2.2rem;
          box-sizing:border-box;
          display:block;
        }
        
        /* View mode: 看起来像纯文字；Edit mode：才显示框 */
        .report-field[disabled]{
          background:transparent;
          color:#111827;
          border-color:transparent;
          box-shadow:none;
          padding:0;
          resize:none;
        }
        body.is-editing .report-field{
          background:#fff;
          border-color:#e5e7eb;
          padding:.1rem .25rem;
        }
        
        .neg{ color:#dc2626 !important; }
        
        .row-del{
          display:none;
          margin-left:6px;
          width:18px;
          height:18px;
          border-radius:999px;
          border:1px solid #e5e7eb;
          background:#fff;
          color:#374151;
          line-height:16px;
          font-size:14px;
          cursor:pointer;
        }
        .row-del:hover{
          background:#fee2e2;
          border-color:#fecaca;
          color:#b91c1c;
        }
        body.is-editing .row-del{
          display:inline-flex;
          align-items:center;
          justify-content:center;
        }
        
        /* Save/Cancel 区 */
        .report-actions{
          margin-top:2.5rem;
          padding-top:1.25rem;
          border-top:1px solid #e5e7eb;
          display:flex;
          gap:.75rem;
          flex-wrap:wrap;
        }
        
        /* ===== 小屏：更舒服 ===== */
        @media (max-width: 900px){
          .report-page-root .report-header-row{ align-items:flex-start; }
          .report-page-root .report-header-row .report-actions-top{
            width:100%;
            justify-content:flex-start;
          }
        }
        </style>        
    <script>
        var monthKeys = @json($monthKeys);
        var monthLabels = @json($monthLabels);
        var sectionNumbers = @json($sectionNumbers);
        @php $sectionCountsForJs = $sectionCounts ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0]; @endphp
        var sectionCounts = @json($sectionCountsForJs);

        function formatAmount(val) {
            if (val === null || val === undefined || val === '' || isNaN(val)) return '';
            var num = Number(val);
            var neg = num < 0;
            var abs = Math.abs(num);
            var txt = abs.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return neg ? '-' + txt : txt;
        }

        function addReportRow(section) {
            var idx = sectionCounts[section];
            var tr = document.createElement('tr');
            var html = '<td class="cf-td"><span class="row-no">' + (idx + 1) + '</span><button type="button" class="row-del" onclick="deleteReportRow(this)" title="{{ __('Delete row') }}">×</button></td>' +
                '<td class="cf-td cf-td--left report-desc-cell"><div class="label-view"></div><textarea name="section_' + section + '_rows[' + idx + '][label]" class="report-label label-edit w-full report-field" style="display:none;"></textarea></td>';
            monthKeys.forEach(function(mk) {
                html += '<td class="cf-td report-amount-cell">' +
                    '<span class="amount-view"></span>' +
                    '<input type="number" step="0.01" name="section_' + section + '_rows[' + idx + '][' + mk + ']" class="report-input report-amount report-field' + (section === 2 ? ' section2-amount' : '') + '" style="display:none;">' +
                    '</td>';
            });
            html += '<td class="cf-td cf-td--amount row-total-cell">0.00</td>';
            tr.innerHTML = html;
            if (section === 1) {
                var totalRow = document.getElementById('section-1-total-row');
                if (totalRow) totalRow.parentNode.insertBefore(tr, totalRow);
            } else if (section === 2) {
                var pendingRow = document.getElementById('section-2-pending-row');
                if (pendingRow) pendingRow.parentNode.insertBefore(tr, pendingRow);
            } else {
                var totalRow = document.getElementById('section-' + section + '-total-row');
                if (totalRow) totalRow.parentNode.insertBefore(tr, totalRow);
            }
            // new rows start disabled; enabling is controlled by Edit button
            tr.querySelectorAll('.report-field').forEach(function(i) { i.setAttribute('disabled', 'disabled'); });
            sectionCounts[section]++;
            if (section === 1 || section === 2) recalcTotals();
            if (section === 2) tr.querySelectorAll('.section2-amount').forEach(function(inp) { inp.addEventListener('input', recalcTotals); });
            tr.querySelectorAll('.report-amount').forEach(function(inp) { inp.addEventListener('input', recalcTotals); });
        }

        function recalcTotals() {
            var part1Body = document.getElementById('section-1-body');
            var part2Body = document.getElementById('section-2-body');
            if (!part1Body) return;

            for (var m = 1; m <= 12; m++) {
                // Part 1 totals
                var part1Total = 0;
                part1Body.querySelectorAll('tr').forEach(function(tr) {
                    if (tr.classList.contains('cf-tfoot')) return;
                    var cell = tr.cells[1 + m];
                    if (!cell) return;
                    var inp = cell.querySelector('input.report-amount');
                    if (inp && inp.value !== '') part1Total += parseFloat(inp.value) || 0;
                });
                var el = document.querySelector('[data-part1-total-m="' + m + '"]');
                if (el) {
                    el.textContent = formatAmount(part1Total);
                    if (part1Total < 0) el.classList.add('neg'); else el.classList.remove('neg');
                }

                // Part 2 pending + total
                var pending = 0;
                if (part2Body) {
                    part2Body.querySelectorAll('tr').forEach(function(tr) {
                        if (tr.classList.contains('cf-tfoot')) return;
                        var cell = tr.cells[1 + m];
                        if (!cell) return;
                        var inp = cell.querySelector('input.section2-amount');
                        if (inp && inp.value !== '') pending += parseFloat(inp.value) || 0;
                    });
                }
                var pendEl = document.querySelector('[data-pending-m="' + m + '"]');
                if (pendEl) {
                    pendEl.textContent = formatAmount(pending);
                    if (pending < 0) pendEl.classList.add('neg'); else pendEl.classList.remove('neg');
                }
                var total2 = part1Total + pending;
                var totEl = document.querySelector('[data-part2-total-m="' + m + '"]');
                if (totEl) {
                    totEl.textContent = formatAmount(total2);
                    if (total2 < 0) totEl.classList.add('neg'); else totEl.classList.remove('neg');
                }

                // Sections 3+ totals
                sectionNumbers.forEach(function(sec) {
                    if (sec < 3) return;
                    var body = document.getElementById('section-' + sec + '-body');
                    if (!body) return;
                    var sectTotal = 0;
                    body.querySelectorAll('tr').forEach(function(tr) {
                        if (tr.classList.contains('cf-tfoot')) return;
                        var cell = tr.cells[1 + m];
                        if (!cell) return;
                        var inp = cell.querySelector('input.report-amount');
                        if (inp && inp.value !== '') sectTotal += parseFloat(inp.value) || 0;
                    });
                    var el = document.querySelector('[data-section-total-m="' + sec + '-' + m + '"]');
                    if (el) {
                        el.textContent = formatAmount(sectTotal);
                        if (sectTotal < 0) el.classList.add('neg'); else el.classList.remove('neg');
                    }
                });
            }

            // Year totals for sections 3+
            sectionNumbers.forEach(function(sec) {
                if (sec < 3) return;
                var body = document.getElementById('section-' + sec + '-body');
                if (!body) return;
                var yearTot = 0;
                for (var m = 1; m <= 12; m++) {
                    body.querySelectorAll('tr').forEach(function(tr) {
                        if (tr.classList.contains('cf-tfoot')) return;
                        var cell = tr.cells[1 + m];
                        if (!cell) return;
                        var inp = cell.querySelector('input.report-amount');
                        if (inp && inp.value !== '') yearTot += parseFloat(inp.value) || 0;
                    });
                }
                var yearEl = document.querySelector('[data-section-year-total="' + sec + '"]');
                if (yearEl) {
                    yearEl.textContent = formatAmount(yearTot);
                    if (yearTot < 0) yearEl.classList.add('neg'); else yearEl.classList.remove('neg');
                }
                var colEl = document.querySelector('[data-section-total-col="' + sec + '"]');
                if (colEl) {
                    colEl.textContent = formatAmount(yearTot);
                    if (yearTot < 0) colEl.classList.add('neg'); else colEl.classList.remove('neg');
                }
            });

            // Row total cells (sum of 12 months per row) and Total column for Part 1 & 2
            function updateRowTotals(body, amountSelector) {
                if (!body) return 0;
                var sectionYearTotal = 0;
                body.querySelectorAll('tr').forEach(function(tr) {
                    if (tr.classList.contains('cf-tfoot')) return;
                    var rowSum = 0;
                    for (var mi = 1; mi <= 12; mi++) {
                        var cell = tr.cells[1 + mi];
                        if (!cell) return;
                        var inp = cell.querySelector(amountSelector || 'input.report-amount');
                        if (inp && inp.value !== '') rowSum += parseFloat(inp.value) || 0;
                    }
                    sectionYearTotal += rowSum;
                    var totalCell = tr.cells[tr.cells.length - 1];
                    if (totalCell && totalCell.classList.contains('row-total-cell')) {
                        totalCell.textContent = formatAmount(rowSum);
                        if (rowSum < 0) totalCell.classList.add('neg'); else totalCell.classList.remove('neg');
                    }
                });
                return sectionYearTotal;
            }
            var part1YearTot = updateRowTotals(part1Body);
            var part1ColEl = document.querySelector('[data-part1-total-col]');
            if (part1ColEl) {
                part1ColEl.textContent = formatAmount(part1YearTot);
                if (part1YearTot < 0) part1ColEl.classList.add('neg'); else part1ColEl.classList.remove('neg');
            }
            var part2YearPending = updateRowTotals(part2Body, 'input.section2-amount');
            var pendColEl = document.querySelector('[data-pending-col]');
            if (pendColEl) {
                pendColEl.textContent = formatAmount(part2YearPending);
                if (part2YearPending < 0) pendColEl.classList.add('neg'); else pendColEl.classList.remove('neg');
            }
            var part2YearTot = part1YearTot + part2YearPending;
            var part2ColEl = document.querySelector('[data-part2-total-col]');
            if (part2ColEl) {
                part2ColEl.textContent = formatAmount(part2YearTot);
                if (part2YearTot < 0) part2ColEl.classList.add('neg'); else part2ColEl.classList.remove('neg');
            }
            sectionNumbers.forEach(function(sec) {
                if (sec < 3) return;
                updateRowTotals(document.getElementById('section-' + sec + '-body'));
            });
        }

        function setEditMode(on) {
            document.body.classList.toggle('is-editing', !!on);
            document.querySelectorAll('.report-field').forEach(function(el) {
                if (on) el.removeAttribute('disabled'); else el.setAttribute('disabled', 'disabled');
            });
            document.querySelectorAll('.section-title-view').forEach(function(el) { el.style.display = on ? 'none' : ''; });
            document.querySelectorAll('.section-title-edit').forEach(function(el) {
                el.style.display = on ? 'inline-block' : 'none';
                if (on) el.removeAttribute('disabled'); else {
                    el.setAttribute('disabled', 'disabled');
                    var view = el.previousElementSibling;
                    if (view && view.classList.contains('section-title-view')) view.textContent = (el.value && el.value.trim()) ? el.value.trim() : (el.placeholder || '');
                }
            });
            // toggle description view/edit blocks
            document.querySelectorAll('.report-desc-cell').forEach(function (cell) {
                var view = cell.querySelector('.label-view');
                var edit = cell.querySelector('.label-edit');
                if (!view || !edit) return;
                if (on) {
                    edit.style.display = '';
                    view.style.display = 'none';
                    edit.value = edit.value || view.textContent || '';
                } else {
                    view.style.display = '';
                    edit.style.display = 'none';
                    view.textContent = edit.value || view.textContent || '';
                }
            });
            // toggle amount view/edit blocks
            document.querySelectorAll('.report-amount-cell').forEach(function (cell) {
                var view = cell.querySelector('.amount-view');
                var input = cell.querySelector('input.report-amount');
                if (!view || !input) return;
                if (on) {
                    input.style.display = '';
                    view.style.display = 'none';
                } else {
                    view.style.display = '';
                    input.style.display = 'none';
                    var v = input.value === '' ? null : (parseFloat(input.value) || 0);
                    view.textContent = v === null ? '' : formatAmount(v);
                }
            });
            var btnSave = document.getElementById('btn-save');
            var btnCancel = document.getElementById('btn-cancel-edit');
            if (btnSave) btnSave.style.display = on ? '' : 'none';
            if (btnCancel) btnCancel.style.display = on ? '' : 'none';
        }

        function updateNegativeStyles() {
            document.querySelectorAll('input.report-amount').forEach(function (inp) {
                var v = inp.value === '' ? null : (parseFloat(inp.value) || 0);
                if (v !== null && v < 0) inp.classList.add('neg'); else inp.classList.remove('neg');
            });
            document.querySelectorAll('.amount-view').forEach(function (span) {
                var txt = span.textContent || '';
                var num = parseFloat(txt.replace(/,/g, ''));
                if (!isNaN(num) && num < 0) span.classList.add('neg'); else span.classList.remove('neg');
            });
        }

        function renumberSection(section) {
            var tbody = document.getElementById('section-' + section + '-body');
            if (!tbody) return;
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (tr) { return !tr.classList.contains('cf-tfoot'); });
            rows.forEach(function (tr, idx) {
                var no = tr.querySelector('.row-no');
                if (no) no.textContent = String(idx + 1);
            });
            sectionCounts[section] = rows.length;
        }

        function deleteReportRow(btn) {
            var tr = btn.closest('tr');
            if (!tr) return;
            var tbody = tr.parentNode;
            tbody.removeChild(tr);
            // identify section by tbody id
            var id = tbody.getAttribute('id') || '';
            var m = id.match(/^section-(\d+)-body$/);
            if (m) renumberSection(parseInt(m[1], 10));
            recalcTotals();
        }

        function cancelReportEdit() {
            setEditMode(false);
            /* 不 history.back() 也不跳转，只退出编辑模式，避免跳到其他年份 */
        }

        document.querySelectorAll('.report-amount').forEach(function(inp) {
            inp.addEventListener('input', function () {
                updateNegativeStyles();
                recalcTotals();
            });
        });
        // Buttons use inline onclick to avoid binding issues
        var form = document.getElementById('report-form');
        if (form) {
            form.addEventListener('submit', function () {
                // ensure disabled fields submit
                document.querySelectorAll('.report-field').forEach(function (el) {
                    el.removeAttribute('disabled');
                });
            });
        }
        // Script is at bottom of page; run immediately
        setEditMode(false);
        updateNegativeStyles();
        recalcTotals();
    </script>
</x-app-layout>
