<x-app-layout>
    <x-slot name="header">
        {{ __('Dashboard') }}
    </x-slot>

    <style>
        .dashboard-pies-row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }
        .dashboard-pie {
            width: 100%;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            margin-bottom: 1rem;
            box-sizing: border-box;
        }
        @media (min-width: 768px) {
            .dashboard-pie { width: 50%; }
        }
        @media (min-width: 1024px) {
            .dashboard-pie { width: 33.333333%; }
        }
    </style>

    <div class="max-w-7xl mx-auto w-full space-y-6">
        @if ($companies->isEmpty())
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                <p class="text-gray-500 text-sm">{{ __('No companies assigned.') }}</p>
            </div>
        @else
            {{-- 資料範圍說明 + 資料摘要（確認有資料時顯示） --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 mb-2">
                <p class="text-sm text-gray-600">
                    {{ __('Cashflow') }}: <a href="{{ route('cashflow.index') }}" class="font-medium text-blue-600 hover:underline">{{ __('View all companies cashflow') }}</a>
                    · {{ $companies->count() }} {{ __('companies') }}
                </p>
                <p class="text-xs text-gray-500 mt-1" id="dashboard-data-summary">
                    {{ __('Deposit') }}: {{ number_format(($depositTotalMinor ?? 0) / 100, 2) }} {{ $baseCurrency }}
                    · {{ __('Withdraw') }}: {{ number_format(($withdrawalTotalMinor ?? 0) / 100, 2) }} {{ $baseCurrency }}
                </p>
            </div>

            {{-- Cashflow: Pie Deposit vs Withdraw (最近 3 个月，每个月一个饼图) --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-2">
                    {{ __('Cashflow') }}: {{ __('Deposit') }} vs {{ __('Withdraw') }} ({{ $baseCurrency }}) – Last 3 months
                </h2>
                @if(!empty($pieLast3))
                    <div class="dashboard-pies-row">
                        @foreach($pieLast3 as $idx => $cell)
                            <div class="dashboard-pie flex flex-col items-center">
                                <div class="text-xs text-gray-600 mb-1 text-center w-full">{{ $cell['label'] }}</div>
                                <div class="mx-auto flex justify-center" style="width: 5cm; height: 5cm;">
                                    <canvas id="chartCashflowPie{{ $idx }}"></canvas>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">{{ __('No cashflow entries.') }}</p>
                @endif
            </div>

            {{-- Cashflow: Horizontal bar – remaining columns (Affin, XE, USDT) --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Cashflow') }}: {{ __('Columns') }} ({{ $baseCurrency }})</h2>
                <div class="h-56 md:h-72">
                    <canvas id="chartCashflowColumns"></canvas>
                </div>
            </div>

            {{-- Companies: Area Chart – Part 1 total last 6 months, all companies --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Part 1') }} {{ __('Total') }} ({{ __('Last 6 months') }}) – {{ __('All companies') }}</h2>
                <div class="h-64 md:h-80">
                    <canvas id="chartPart1Area"></canvas>
                </div>
            </div>

            {{-- Companies: Line Chart – Part 2 total by month, multiple companies --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Part 2') }} {{ __('Total') }} {{ __('by month') }}</h2>
                <div class="h-64 md:h-80">
                    <canvas id="chartPart2Line"></canvas>
                </div>
            </div>
        @endif
    </div>

    @if ($companies->isNotEmpty())
    <script>
        (function() {
            const baseCurrency = @json($baseCurrency);
            const colorPalette = [
                'rgb(59, 130, 246)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)',
                'rgb(239, 68, 68)', 'rgb(139, 92, 246)', 'rgb(236, 72, 153)',
                'rgb(20, 184, 166)', 'rgb(251, 146, 60)'
            ];
            const pieLabels = @json([__('Deposit'), __('Withdraw')]);
            const depositMinor = {{ (int)($depositTotalMinor ?? 0) }};
            const withdrawalMinor = {{ (int)($withdrawalTotalMinor ?? 0) }};
            const columnTotals = @json($columnTotals ?? ['Affin' => 0, 'XE USDT' => 0, 'Total' => 0]);
            const part1Last6Labels = @json($part1Last6Labels ?? []);
            const part1Last6Series = @json($part1Last6Series ?? []);
            const part2Labels = @json($part2Labels ?? []);
            const part2Series = @json($part2Series ?? []);
            const pieLast3 = @json($pieLast3 ?? []);

            function toAmount(minor) { return Math.round(Number(minor)) / 100; }

            function initCharts() {
                if (typeof Chart === 'undefined') {
                    document.querySelectorAll('[id^="chart"]').forEach(function(el) {
                        el.parentElement.innerHTML = '<p class="text-sm text-amber-700">Chart.js failed to load. Check network or try again.</p>';
                    });
                    return;
                }
                try {
                    if (Array.isArray(pieLast3) && pieLast3.length) {
                        pieLast3.forEach(function(item, idx) {
                            var pieCtx = document.getElementById('chartCashflowPie' + idx);
                            if (!pieCtx) return;
                            new Chart(pieCtx, {
                                type: 'pie',
                                data: {
                                    labels: pieLabels,
                                    datasets: [{
                                        data: [toAmount(item.deposit_minor || 0), toAmount(item.withdrawal_minor || 0)],
                                        backgroundColor: ['rgba(16, 185, 129, 0.8)', 'rgba(239, 68, 68, 0.8)'],
                                        borderColor: ['rgb(16, 185, 129)', 'rgb(239, 68, 68)'],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { position: 'bottom' },
                                        tooltip: {
                                            callbacks: {
                                                label: function(ctx) {
                                                    var v = ctx.raw;
                                                    return ctx.label + ': ' + baseCurrency + ' ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2 });
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        });
                    }
                } catch (e) { console.error('Dashboard pie charts (last 3 months):', e); }

                try {
                    var colLabels = Object.keys(columnTotals);
                    var colValues = colLabels.map(function(k) { return toAmount(columnTotals[k] || 0); });
                    var colCtx = document.getElementById('chartCashflowColumns');
                    if (colCtx) {
                        new Chart(colCtx, {
                            type: 'bar',
                            data: {
                                labels: colLabels,
                                datasets: [{
                                    label: baseCurrency,
                                    data: colValues,
                                    backgroundColor: colorPalette.slice(0, 3).map(function(c) {
                                        return c.replace('rgb', 'rgba').replace(')', ', 0.7)');
                                    }),
                                    borderColor: colorPalette.slice(0, 3),
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function(ctx) {
                                                return baseCurrency + ' ' + Number(ctx.raw).toLocaleString('en-US', { minimumFractionDigits: 2 });
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        ticks: { callback: function(v) { return baseCurrency + ' ' + v.toLocaleString(); } }
                                    }
                                }
                            }
                        });
                    }
                } catch (e) { console.error('Dashboard columns chart:', e); }

                try {
                    var part1Ctx = document.getElementById('chartPart1Area');
                    if (part1Ctx && part1Last6Series.length) {
                        var part1Gradients = [];
                        part1Last6Series.forEach(function(_, i) {
                            var g = part1Ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
                            var c = colorPalette[i % colorPalette.length];
                            var rgb = c.replace('rgb(', '').replace(')', '').split(',').map(Number);
                            g.addColorStop(0, 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',0.5)');
                            g.addColorStop(1, 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',0.05)');
                            part1Gradients.push(g);
                        });
                        new Chart(part1Ctx, {
                            type: 'line',
                            data: {
                                labels: part1Last6Labels,
                                datasets: part1Last6Series.map(function(s, i) {
                                    return {
                                        label: s.name,
                                        data: s.data,
                                        borderColor: colorPalette[i % colorPalette.length],
                                        backgroundColor: part1Gradients[i],
                                        fill: true,
                                        tension: 0.3
                                    };
                                })
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { position: 'top' },
                                    tooltip: { mode: 'index', intersect: false }
                                },
                                scales: {
                                    y: { stacked: true, beginAtZero: true, ticks: { callback: function(v) { return v.toLocaleString(); } } },
                                    x: {
                                        stacked: true,
                                        ticks: { autoSkip: false }
                                    }
                                }
                            }
                        });
                    }
                } catch (e) { console.error('Dashboard Part1 area chart:', e); }

                try {
                    var part2Ctx = document.getElementById('chartPart2Line');
                    if (part2Ctx && part2Series.length) {
                        new Chart(part2Ctx, {
                            type: 'line',
                            data: {
                                labels: part2Labels,
                                datasets: part2Series.map(function(s, i) {
                                    return {
                                        label: s.name,
                                        data: s.data,
                                        borderColor: colorPalette[i % colorPalette.length],
                                        backgroundColor: colorPalette[i % colorPalette.length].replace('rgb', 'rgba').replace(')', ', 0.1)'),
                                        fill: false,
                                        tension: 0.3
                                    };
                                })
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { position: 'top' },
                                    tooltip: { mode: 'index', intersect: false }
                                },
                                scales: {
                                    y: { beginAtZero: true, ticks: { callback: function(v) { return v.toLocaleString(); } } },
                                    x: { ticks: { autoSkip: false } }
                                }
                            }
                        });
                    }
                } catch (e) { console.error('Dashboard Part2 line chart:', e); }
            }

            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
            s.crossOrigin = 'anonymous';
            s.onload = initCharts;
            s.onerror = function() {
                document.querySelectorAll('[id^="chart"]').forEach(function(el) {
                    if (el.parentElement) el.parentElement.innerHTML = '<p class="text-sm text-amber-700">Chart.js could not be loaded. Check your network.</p>';
                });
            };
            document.head.appendChild(s);
        })();
    </script>
    @else
    <script>
        console.log('Dashboard: no companies assigned. Assign companies to see charts.');
    </script>
    @endif
</x-app-layout>
