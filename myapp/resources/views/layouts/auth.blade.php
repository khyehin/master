<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Master - {{ $title ?? __('Login') }}</title>
    <link rel="icon" href="{{ asset('images/master-icon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        .auth-lang-dropdown {
            position: absolute; right: 0; top: calc(100% + 6px);
            min-width: 8rem;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(0,0,0,.08);
            overflow: hidden;
            z-index: 50;
        }
        .auth-lang-dropdown a {
            display: block;
            padding: 10px 14px;
            font-size: .875rem;
            color: #0f172a;
            text-decoration: none;
        }
        .auth-lang-dropdown a:hover { background: #f8fafc; }
        .auth-page {
            min-height: 100vh;
            background: #eef1f6;
        }
        .auth-main { padding: 3cm 10cm; }
        @media (max-width: 768px) { .auth-main { padding: 2cm 1.5cm; } }
        .login-box {
            background: #fff;
            width: 380px;
            max-width: 100%;
            padding: 30px 34px 34px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            overflow: visible;
        }
    </style>
</head>
<body class="antialiased text-slate-900 auth-page flex flex-col" style="font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;" x-data="{ langOpen: false }">

    <header class="h-16 shrink-0 flex items-center justify-between px-6 sm:px-10 bg-white/70 backdrop-blur-md border-b border-slate-200/60">
        <div class="flex items-center shrink-0">
            <img src="{{ asset('images/master-logo.png') }}" alt="Master" class="h-8 max-w-[120px] w-auto object-contain object-left">
        </div>
        <div class="relative">
            <button type="button" @click="langOpen = !langOpen"
                    class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900 px-3 py-2 rounded-full bg-slate-100 hover:bg-slate-200 transition">
                @if(app()->getLocale() === 'en') EN
                @elseif(app()->getLocale() === 'zh') 中文
                @else MS
                @endif
                <span class="text-slate-400 text-[10px]">▼</span>
            </button>
            <div x-show="langOpen" x-cloak @click.outside="langOpen = false" class="auth-lang-dropdown">
                <a href="{{ route('locale.switch', 'en') }}">English</a>
                <a href="{{ route('locale.switch', 'zh') }}">中文</a>
                <a href="{{ route('locale.switch', 'ms') }}">Malay</a>
            </div>
        </div>
    </header>

    {{-- 中間登入區：上下 3cm、左右 10cm，置中對齊；可捲動以看到按鈕 --}}
    <main class="flex-1 flex flex-col lg:flex-row items-center justify-center auth-main min-h-0 overflow-y-auto">
        <div class="hidden lg:flex lg:flex-1 lg:items-center lg:justify-center">
            <img src="{{ asset('images/master-logo.png') }}" alt="Master" class="max-w-[280px] w-full h-auto object-contain">
        </div>

        <div class="w-full lg:flex-1 flex items-center justify-center mx-auto">
            <div class="login-box">
                <div class="flex justify-center mb-4 min-h-[80px]">
                    <img src="{{ asset('images/master-logo.png') }}" alt="Master" class="max-w-[220px] w-full h-auto object-contain" loading="eager">
                </div>
                <h2 class="text-center text-slate-600 text-lg mb-5">{{ __('Login') }}</h2>
                @if(session('status'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">{{ session('status') }}</div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="shrink-0 border-t border-slate-200 bg-white/50 py-3 flex items-center justify-center">
        <span class="text-xs text-slate-500 flex items-center justify-center gap-1">© {{ date('Y') }} <img src="{{ asset('images/master-logo.png') }}" alt="Master" class="inline-block h-4 w-auto max-h-5 align-baseline"></span>
    </footer>

</body>
</html>
