<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Master - {{ $title ?? ($header ?? '') }}</title>
    <link rel="icon" href="{{ asset('images/master-icon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .topbar-toggle { width: 2.5rem; height: 2.5rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; border: none; background: transparent; color: #000; cursor: pointer; }
        .topbar-toggle:hover { background: rgba(0,0,0,0.06); }
        .topbar-divider { width: 1px; height: 1.5rem; background: #d1d5db; margin: 0 0.75rem; }
        .sidebar-transition { transition: width 0.2s ease, min-width 0.2s ease; overflow: hidden; }
        .sidebar-wrap { border-right: 1px solid #d1d5db; box-shadow: 1px 0 6px rgba(0,0,0,0.04); }
        .sidebar-link.active { background: #e5e7eb; color: #000; }
        .lang-dropdown { position: absolute; right: 0; top: 100%; margin-top: 4px; background: #fff; border: 1px solid #d1d5db; border-radius: 4px; min-width: 7rem; z-index: 300; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .lang-dropdown a { display: block; padding: 8px 12px; font-size: 0.875rem; text-decoration: none; color: #000; }
        .lang-dropdown a:hover { background: #e5e7eb; }
        .topbar-lang-btn, .topbar-logout-btn { border: none; background: transparent; color: #000; cursor: pointer; font-size: 0.875rem; padding: 0.25rem 0.5rem; }
        .topbar-lang-btn:hover, .topbar-logout-btn:hover { color: #374151; text-decoration: underline; }
        .content-layer { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); min-height: 0; }
        [x-cloak] { display: none !important; }
        /* Date range picker - 内联保证样式一定生效 */
        .date-filter-wrapper { position: relative; max-width: 260px; }
        .drp-display-input { cursor: pointer; background-color: #fff; }
        .drp-container { position: fixed; z-index: 99999; background: transparent; padding: 0; display: none; min-width: 408px; overflow: visible; }
        .drp-container.drp-container-open { display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; gap: 12px; align-items: stretch; overflow: visible; }
        .drp-wrapper { width: 260px; min-width: 260px; flex-shrink: 0; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 12px; }
        .drp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .drp-month-label { font-size: 12px; font-weight: 600; color: #111827; }
        .drp-nav { border: none; background: #f3f4f6; border-radius: 999px; width: 22px; height: 22px; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
        .drp-nav:hover { background: #e5e7eb; }
        .drp-week-row { display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; gap: 2px; font-size: 10px; text-align: center; color: #6b7280; margin-bottom: 4px; width: 100%; }
        .drp-week-row > div, .drp-week-row > span.drp-week-cell { flex: 0 0 calc((100% - 12px) / 7); width: calc((100% - 12px) / 7); white-space: nowrap; min-width: 0; overflow: hidden; text-align: center; }
        .drp-week-row .drp-week-cell { display: inline-block; box-sizing: border-box; }
        .drp-grid { display: grid !important; grid-template-columns: repeat(7, 1fr) !important; grid-auto-flow: row !important; gap: 2px; font-size: 11px; width: 100%; min-width: 0; }
        .drp-grid > div { padding: 4px 0; text-align: center; border-radius: 4px; cursor: pointer; min-width: 0; }
        .drp-grid > div.is-empty { cursor: default; }
        .drp-grid > div:not(.is-empty):hover { background: #e5e7eb; }
        .drp-grid > div.is-start, .drp-grid > div.is-end { background: #3b82f6; color: #fff; font-weight: 500; }
        .drp-grid > div.is-in-range { background: #93c5fd; color: #1e3a8a; }
        .drp-quick-bar { display: flex; flex-direction: column; gap: 6px; width: 120px; min-width: 120px; flex-shrink: 0; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 12px; }
        .drp-quick-item { border: none; background: #f3f4f6; border-radius: 6px; padding: 6px 10px; font-size: 12px; cursor: pointer; text-align: left; color: #374151; }
        .drp-quick-item:hover { background: #e5e7eb; }
        /* User list – 浅色边框、圆角，与 cashflow 表一致 */
        .user-new-cell { display: inline-block; padding: 0.35rem 0.6rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; }
        .user-new-link { font-size: 0.875rem; color: #374151; text-decoration: none; }
        .user-new-link:hover { color: #111827; }
        .user-list-table { border-collapse: separate; border-spacing: 0; font-size: 0.9375rem; width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        .user-list-table .user-th { padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border: none; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; text-align: center; vertical-align: middle; }
        .user-list-table .user-th:last-child { border-right: none; }
        .user-list-table .user-td { padding: 0.75rem 1rem; color: #111827; background: #fff; border: none; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; text-align: center; vertical-align: middle; }
        .user-list-table .user-td:last-child { border-right: none; }
        .user-list-table tbody tr:last-child .user-td { border-bottom: none; }
        .user-edit-link { display: inline-block; font-size: 0.9375rem; font-weight: 500; color: #374151; text-decoration: none; padding: 0.25rem 0.5rem; border-radius: 6px; }
        .user-edit-link:hover { color: #111827; background: #e5e7eb; }
        .user-row--empty .user-td-empty { padding: 2.5rem 1rem; text-align: center; vertical-align: middle; color: #6b7280; font-size: 0.9375rem; border: none; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
        .user-list-table .user-row--empty .user-td-empty:last-child { border-right: none; }
        /* User create/edit – 标签在上输入在下 + 按钮框 */
        .user-form-field { margin-bottom: 1.25rem; }
        .user-form-label { display: block !important; font-size: 0.8125rem; font-weight: 500; color: #4b5563; margin-bottom: 0.5rem; }
        .user-form-input { display: block !important; width: 100%; height: 2.5rem; box-sizing: border-box; border-radius: 6px; border: 1px solid #d1d5db; padding: 0 0.75rem; font-size: 0.875rem; background: #fff; }
        .user-form-input:focus { border-color: #6b7280; outline: none; }
        .user-form-actions-wrap { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: center; gap: 1rem; }
        .user-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 6rem; height: 2.5rem; padding: 0 1.25rem; font-size: 0.875rem; font-weight: 500; border-radius: 6px; text-decoration: none; border: 1px solid #374151; cursor: pointer; box-sizing: border-box; }
        .user-btn--primary { background: #374151; color: #fff; }
        .user-btn--primary:hover { background: #1f2937; color: #fff; }
        .user-btn--secondary { background: #fff; color: #374151; }
        .user-btn--secondary:hover { background: #e5e7eb; color: #111827; }
        /* Cashflow table – 浅色边框、圆角 */
        .cf-table { border-collapse: separate; border-spacing: 0; font-size: 0.9375rem; width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        .cf-table .cf-th { padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #fef9c3; border: none; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; text-align: center; vertical-align: middle; }
        .cf-table .cf-th:last-child { border-right: none; }
        .cf-table .cf-td { padding: 0.75rem 1rem; color: #111827; background: #fff; border: none; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; text-align: center; vertical-align: middle; }
        .cf-table .cf-td:last-child { border-right: none; }
        .cf-table tbody tr:last-child .cf-td { border-bottom: none; }
        .cf-table .cf-td.cf-td--left { text-align: left; }
        .cf-table .cf-td.cf-td--amount { font-variant-numeric: tabular-nums; }
        .cf-table .cf-td.cf-td--withdrawal { color: #dc2626; }
        .cf-table .cf-tfoot .cf-td { font-weight: 600; background: #f9fafb; border-top: 1px solid #e5e7eb; }
        .cf-table .cf-tfoot .cf-td.cf-td--withdrawal { color: #dc2626; }
        /* 全站：内容区可横向+纵向滑动；大屏宽内容时左右滚，小屏由各页 reflow + 表内滚 */
        .content-layer { min-width: 0; max-width: 100%; overflow-x: auto; overflow-y: visible; -webkit-overflow-scrolling: touch; touch-action: pan-x pan-y; }
        @media (max-width: 768px) {
            main { padding: 0.75rem !important; }
            .content-layer { padding: 0.75rem !important; }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100 flex flex-col min-h-screen overflow-x-hidden" x-data="{ sidebarOpen: true }" x-init="sidebarOpen = window.innerWidth >= 768">
    <div id="drp-portal" aria-hidden="true"></div>
    <!-- Topbar：小屏可换行、不溢出 -->
    <header class="shrink-0 flex flex-wrap items-center justify-between gap-2 bg-white border-b border-gray-300 px-3 py-2 sm:px-6 sm:py-0 sm:h-14 min-h-[3rem]">
        <div class="flex items-center gap-2 min-w-0">
            <button type="button" @click="sidebarOpen = ! sidebarOpen" class="topbar-toggle" aria-label="Toggle sidebar">☰</button>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                <img src="{{ asset('images/master-logo.png') }}" alt="Master" class="h-8 max-w-[120px] w-auto object-contain object-left">
            </a>
            <span class="topbar-divider hidden sm:inline"></span>
            <span class="text-black truncate max-w-[12rem] sm:max-w-none">@isset($header){{ $header }}@else Dashboard @endisset</span>
        </div>
        <div class="flex items-center gap-2 sm:gap-4">
            <div class="relative" x-data="{ langOpen: false }">
                <button type="button" @click="langOpen = ! langOpen" class="topbar-lang-btn font-medium">
                    @if(app()->getLocale() === 'en') EN
                    @elseif(app()->getLocale() === 'zh') 中文
                    @else Malay
                    @endif
                    ▾
                </button>
                <div x-show="langOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     @click.outside="langOpen = false"
                     x-cloak
                     class="lang-dropdown">
                    <a href="{{ route('locale.switch', 'en') }}">EN</a>
                    <a href="{{ route('locale.switch', 'zh') }}">中文</a>
                    <a href="{{ route('locale.switch', 'ms') }}">Malay</a>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="topbar-logout-btn font-semibold">{{ __('Log Out') }}</button>
            </form>
        </div>
    </header>

    <div class="flex flex-1 min-h-0 overflow-hidden">
        <!-- Sidebar: 展开有宽度，收起宽度为 0 完全隐藏；右侧黑线 + 阴影与内容区分 -->
        <aside class="sidebar-transition flex flex-col bg-white shrink-0 sidebar-wrap"
               :style="sidebarOpen ? 'width: 14rem; min-width: 14rem;' : 'width: 0; min-width: 0; border-right-width: 0; box-shadow: none;'">
            <nav class="flex-1 overflow-y-auto py-4">
                <div class="px-3 space-y-1" style="min-width: 13rem;">
                    <div class="text-xs font-semibold text-black uppercase tracking-wider pt-2 pb-1">Dashboard</div>
                    <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center px-3 py-2 rounded text-sm font-medium text-black {{ request()->routeIs('dashboard') ? 'sidebar-link active' : 'hover:bg-gray-300' }}">Dashboard</a>

                    <div class="text-xs font-semibold text-black uppercase tracking-wider pt-4 pb-1">Company</div>
                    <a href="{{ route('companies.index') }}" class="sidebar-link flex items-center px-3 py-2 rounded text-sm font-medium text-black {{ request()->routeIs('companies.*') && !request()->routeIs('cashflow.*') ? 'sidebar-link active' : 'hover:bg-gray-300' }}">Companies</a>

                    <div class="text-xs font-semibold text-black uppercase tracking-wider pt-4 pb-1">Cashflow</div>
                    <a href="{{ route('cashflow.index') }}" class="sidebar-link flex items-center px-3 py-2 rounded text-sm font-medium text-black {{ request()->routeIs('cashflow.*') ? 'sidebar-link active' : 'hover:bg-gray-300' }}">Cashflow</a>

                    <div class="text-xs font-semibold text-black uppercase tracking-wider pt-4 pb-1">Setting</div>
                    <a href="{{ route('setting.audit-log') }}" class="sidebar-link flex items-center px-3 py-2 rounded text-sm font-medium text-black {{ request()->routeIs('setting.audit-log') ? 'sidebar-link active' : 'hover:bg-gray-300' }}">Audit log</a>
                    <a href="{{ route('setting.roles') }}" class="sidebar-link flex items-center px-3 py-2 rounded text-sm font-medium text-black {{ request()->routeIs('setting.roles') ? 'sidebar-link active' : 'hover:bg-gray-300' }}">Role</a>
                    <a href="{{ route('setting.users') }}" class="sidebar-link flex items-center px-3 py-2 rounded text-sm font-medium text-black {{ request()->routeIs('setting.users') ? 'sidebar-link active' : 'hover:bg-gray-300' }}">User</a>
                </div>
            </nav>
        </aside>

        <div class="flex flex-1 flex-col min-w-0 bg-gray-100 overflow-hidden">
            <main class="flex-1 min-w-0 overflow-auto p-4 sm:p-6" style="-webkit-overflow-scrolling: touch;">
                <div class="content-layer p-4 sm:p-6 min-w-0 w-full">
                    {{ $slot }}
                </div>
            </main>
            <footer class="shrink-0 border-t border-gray-300 bg-white py-3 px-4 sm:px-6 text-center text-sm text-gray-700">
                &copy; {{ date('Y') }} <img src="{{ asset('images/master-logo.png') }}" alt="Master" class="inline-block h-4 w-auto max-h-5 align-baseline"> {{ __('All rights reserved.') }}
            </footer>
        </div>
    </div>
</body>
</html>
