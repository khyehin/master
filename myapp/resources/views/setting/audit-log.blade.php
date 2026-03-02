<x-app-layout>
    <x-slot name="header">
        {{ __('Audit log') }}
    </x-slot>
    <div class="max-w-7xl mx-auto w-full space-y-6">
        {{-- Card: title + filters --}}
        <div class="bg-white border border-gray-300 rounded-lg p-6 shadow-sm">
            <div class="mb-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Security') }}</div>
                <h1 class="text-xl font-semibold text-gray-900 mt-1">{{ __('Audit Logs') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('Track who did what and when.') }}</p>
            </div>

            <form method="get" action="{{ route('setting.audit-log') }}" id="audit-filter-form" class="space-y-5 overflow-visible">
                {{-- Filters row: date range + user, action, keyword, per page, buttons (all same row height) --}}
                <div class="flex flex-wrap gap-4 items-end border-t border-gray-200 pt-5 overflow-visible">
                    <x-date-range-picker
                        :date-from="$filters['date_from']"
                        :date-to="$filters['date_to']"
                        :date-all="$filters['date_all'] ?? '0'"
                    />
                    <div class="flex flex-col gap-1 min-w-[160px]">
                        <label class="text-xs font-medium text-gray-500">{{ __('User') }}</label>
                        <select name="user_id" class="audit-filter-input rounded border border-gray-300 px-2.5 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-400 w-full h-[34px]">
                            <option value="0">{{ __('All users') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ $filters['user_id'] === $u->id ? 'selected' : '' }}>
                                    {{ $u->username ?: 'user#'.$u->id }}{{ $u->name ? ' · '.$u->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1 min-w-[160px]">
                        <label class="text-xs font-medium text-gray-500">{{ __('Action') }}</label>
                        <select name="action" class="audit-filter-input rounded border border-gray-300 px-2.5 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-400 w-full h-[34px]">
                            <option value="">{{ __('All actions') }}</option>
                            @foreach($actions as $ac)
                                <option value="{{ $ac }}" {{ $filters['action'] === $ac ? 'selected' : '' }}>{{ $ac }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1 min-w-[200px] flex-1 max-w-sm">
                        <label class="text-xs font-medium text-gray-500">{{ __('Keyword') }}</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}"
                               placeholder="{{ __('Search action or details...') }}"
                               class="audit-filter-input rounded border border-gray-300 px-2.5 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-400 w-full h-[34px]">
                    </div>
                    <div class="flex flex-col gap-1 min-w-[90px]">
                        <label class="text-xs font-medium text-gray-500">{{ __('Per page') }}</label>
                        <select name="per_page" class="audit-filter-input rounded border border-gray-300 px-2.5 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-400 w-full h-[34px]">
                            @foreach($per_page_options as $opt)
                                <option value="{{ $opt }}" {{ $filters['per_page'] === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1 items-end">
                        <label class="text-xs font-medium text-gray-500 invisible">&#8203;</label>
                        <div class="flex gap-2 h-[34px] items-center">
                            <button type="submit" class="rounded border border-gray-300 bg-gray-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-700 h-[34px] inline-flex items-center justify-center">
                                {{ __('Apply') }}
                            </button>
                            <a href="{{ route('setting.audit-log') }}" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 h-[34px] inline-flex items-center justify-center">
                                {{ __('Reset') }}
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table card: extra top spacing so header row is not stuck to filter card --}}
        <div class="bg-white border border-gray-300 rounded-lg overflow-hidden shadow-sm mt-2">
            <div class="overflow-x-auto max-h-[calc(100vh-20rem)] overflow-y-auto">
                <table class="w-full border-collapse text-left text-sm min-w-[800px]">
                    <thead class="sticky top-0 z-10 bg-gray-50 border-b border-gray-300 shadow-sm">
                        <tr>
                            <th class="p-3 border-r border-gray-200 font-semibold text-gray-700 w-40">{{ __('Time') }}</th>
                            <th class="p-3 border-r border-gray-200 font-semibold text-gray-700 w-32">{{ __('User') }}</th>
                            <th class="p-3 border-r border-gray-200 font-semibold text-gray-700 w-28">{{ __('Action') }}</th>
                            <th class="p-3 border-r border-gray-200 font-semibold text-gray-700 w-24">{{ __('Entity') }}</th>
                            <th class="p-3 border-r border-gray-200 font-semibold text-gray-700">{{ __('Description') }}</th>
                            <th class="p-3 border-r border-gray-200 font-semibold text-gray-700 w-52">{{ __('Extra') }}</th>
                            <th class="p-3 font-semibold text-gray-700 w-28">{{ __('IP') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $details = is_array($log->details) ? $log->details : [];
                                $refTable = $details['ref_table'] ?? '';
                                $refId = $details['ref_id'] ?? null;
                                $desc = isset($details['description']) && is_scalar($details['description']) ? (string)$details['description'] : '';
                                $extraArr = array_diff_key($details, array_flip(['description', '_ip', 'ref_table', 'ref_id']));
                                $extraPairs = [];
                                foreach ($extraArr as $k => $v) {
                                    $extraPairs[] = $k . ': ' . (is_scalar($v) || $v === null ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE));
                                }
                                $extraText = implode('; ', $extraPairs);
                                $extraShort = $extraText !== '' && mb_strlen($extraText) > 120 ? mb_substr($extraText, 0, 120) . '…' : $extraText;
                                $ip = $log->ip_address ?? ($details['_ip'] ?? '');
                            @endphp
                            <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                                <td class="p-3 border-r border-gray-100 text-gray-800 align-top">
                                    <span class="whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                                    <span class="block text-xs text-gray-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="p-3 border-r border-gray-100 align-top">
                                    @if($log->user)
                                        <span class="inline-flex items-center text-xs px-2 py-1 rounded-md bg-blue-50 text-blue-800 border border-blue-100">
                                            {{ $log->user->username ?: $log->user->name ?: 'user#'.$log->user->id }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('System') }}</span>
                                    @endif
                                </td>
                                <td class="p-3 border-r border-gray-100 align-top">
                                    @if($log->event_type !== '')
                                        <span class="inline-flex items-center text-xs px-2 py-1 rounded-md bg-violet-50 text-violet-800 border border-violet-100">
                                            {{ $log->event_type }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">–</span>
                                    @endif
                                </td>
                                <td class="p-3 border-r border-gray-100 text-gray-700 text-xs align-top">
                                    @if($refTable !== '')
                                        <span class="font-medium">{{ $refTable }}</span>
                                        @if($refId !== null && $refId !== '')
                                            <span class="text-gray-500">#{{ $refId }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">–</span>
                                    @endif
                                </td>
                                <td class="p-3 border-r border-gray-100 text-gray-700 text-xs align-top max-w-[200px]">
                                    {{ $desc !== '' ? e($desc) : '–' }}
                                </td>
                                <td class="p-3 border-r border-gray-100 align-top">
                                    @if($extraShort !== '')
                                        <div class="text-xs text-gray-600 max-w-[280px] truncate" title="{{ e($extraText) }}">{{ e($extraShort) }}</div>
                                        @if(mb_strlen($extraText) > 120)
                                            <details class="mt-1">
                                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">{{ __('Show more') }}</summary>
                                                <pre class="mt-1 p-2 bg-gray-50 rounded text-xs overflow-x-auto whitespace-pre-wrap break-words border border-gray-100">{{ e($extraText) }}</pre>
                                            </details>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">–</span>
                                    @endif
                                </td>
                                <td class="p-3 text-gray-700 text-xs font-mono align-top">{{ $ip !== '' ? e($ip) : '–' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center">
                                    <div class="inline-flex flex-col items-center gap-2 text-gray-500">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <p class="font-medium text-gray-600">{{ __('No audit records found') }}</p>
                                        <p class="text-sm">{{ __('Try a different date range or clear filters.') }}</p>
                                        <a href="{{ route('setting.audit-log') }}" class="text-sm text-gray-600 underline hover:no-underline mt-1">{{ __('Clear filters') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages() && $logs->total() > 0)
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
                    <div class="flex items-center gap-4">
                        <span>
                            {{ __('Showing :from–:to of :total', [
                                'from' => $logs->firstItem(),
                                'to' => $logs->lastItem(),
                                'total' => $logs->total(),
                            ]) }}
                        </span>
                        <span class="text-gray-400">·</span>
                        <span>{{ __('Page :current of :last', ['current' => $logs->currentPage(), 'last' => $logs->lastPage()]) }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        @if($logs->onFirstPage())
                            <span class="rounded border border-gray-200 bg-gray-100 px-3 py-1.5 text-gray-400 cursor-not-allowed text-xs">← {{ __('Prev') }}</span>
                        @else
                            <a href="{{ $logs->previousPageUrl() }}" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-gray-700 hover:bg-gray-50 text-xs">← {{ __('Prev') }}</a>
                        @endif
                        @php
                            $current = $logs->currentPage();
                            $last = $logs->lastPage();
                            $start = max(1, $current - 2);
                            $end = min($last, $current + 2);
                        @endphp
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $current)
                                <span class="rounded border border-gray-300 bg-gray-200 px-3 py-1.5 text-gray-800 font-medium text-xs">{{ $i }}</span>
                            @else
                                <a href="{{ $logs->url($i) }}" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-gray-700 hover:bg-gray-50 text-xs">{{ $i }}</a>
                            @endif
                        @endfor
                        @if($logs->hasMorePages())
                            <a href="{{ $logs->nextPageUrl() }}" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-gray-700 hover:bg-gray-50 text-xs">{{ __('Next') }} →</a>
                        @else
                            <span class="rounded border border-gray-200 bg-gray-100 px-3 py-1.5 text-gray-400 cursor-not-allowed text-xs">{{ __('Next') }} →</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
