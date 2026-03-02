@php
    $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $monthKeys = \App\Models\CompanyReportRow::monthKeys();
    $fmt = function ($v) {
        if ($v === null || $v === '') return '';
        return number_format((float) $v, 2, '.', ',');
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $company->name }} – {{ $year }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        table { border-collapse: collapse; margin-bottom: 1.5em; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #fef9c3; font-weight: bold; text-align: center; }
        td.num { text-align: right; }
        td.desc { font-weight: bold; }
        tr.total-row td { font-weight: bold; background: #f9fafb; }
        tr.part-title td { background: #fef3c7; font-weight: bold; font-size: 12pt; }
        .neg { color: #dc2626; }
    </style>
</head>
<body>
    <h1>{{ $company->name }} – {{ __('Monthly report') }} {{ $year }}</h1>

    @foreach([1, 2, 3, 4] as $section)
        @php
            $rows = $rowsBySection[$section];
            $sectionTitles = [
                1 => __('Part 1') . ' – ' . __('Year/Month') . ' ' . $year,
                2 => __('Part 2') . ' – ' . __('Nett') . ' ' . $year,
                3 => __('Part 3') . ' – ' . __('Nett') . ' ' . $year,
                4 => __('Part 4') . ' – ' . __('Open Capital') . ' ' . $year,
            ];
            $totals = array_fill(0, 12, 0.0);
        @endphp
        <table>
            <tr class="part-title">
                <td colspan="{{ 14 }}" style="border:1px solid #d1d5db;">{{ $sectionTitles[$section] }}</td>
            </tr>
            <tr>
                <th style="width:3em;">{{ __('No') }}</th>
                <th style="min-width:140px;">{{ __('Description') }}</th>
                @foreach($monthLabels as $m)
                    <th class="num" style="min-width:5em;">{{ $m }}</th>
                @endforeach
            </tr>
            @foreach($rows as $i => $r)
                @php $rowTotal = 0.0; @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td class="desc">{{ $r->label ?? '' }}</td>
                    @foreach($monthKeys as $idx => $mk)
                        @php
                            $v = $r->$mk !== null ? (float) $r->$mk : null;
                            if ($v !== null) { $totals[$idx] += $v; $rowTotal += $v; }
                        @endphp
                        <td class="num"{{ $v !== null && $v < 0 ? ' style="color:#dc2626;"' : '' }}>{{ $v !== null ? $fmt($v) : '' }}</td>
                    @endforeach
                </tr>
            @endforeach
            {{-- Part 2: Pending row then Total (Part1 + Pending). Other parts: single Total row. --}}
            @if($section == 2)
                @php
                    $part1Totals = array_fill(0, 12, 0.0);
                    foreach ($rowsBySection[1] as $r) {
                        foreach ($monthKeys as $idx => $mk) {
                            $v = $r->$mk !== null ? (float) $r->$mk : 0.0;
                            $part1Totals[$idx] += $v;
                        }
                    }
                    $pending = $totals;
                    $part2Total = [];
                    for ($i = 0; $i < 12; $i++) $part2Total[$i] = $part1Totals[$i] + $pending[$i];
                @endphp
                <tr class="total-row">
                    <td></td>
                    <td class="desc">{{ __('Pending Amount') }}</td>
                    @foreach($pending as $v)
                        <td class="num"{{ $v < 0 ? ' style="color:#dc2626;"' : '' }}>{{ $fmt($v) }}</td>
                    @endforeach
                </tr>
                <tr class="total-row">
                    <td></td>
                    <td class="desc">{{ __('Total') }}</td>
                    @foreach($part2Total as $v)
                        <td class="num"{{ $v < 0 ? ' style="color:#dc2626;"' : '' }}>{{ $fmt($v) }}</td>
                    @endforeach
                </tr>
            @else
                <tr class="total-row">
                    <td></td>
                    <td class="desc">{{ __('Total') }}</td>
                    @foreach($totals as $idx => $sum)
                        <td class="num"{{ $sum < 0 ? ' style="color:#dc2626;"' : '' }}>{{ $fmt($sum) }}</td>
                    @endforeach
                </tr>
            @endif
        </table>
    @endforeach
</body>
</html>
