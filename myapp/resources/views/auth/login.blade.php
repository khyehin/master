@extends('layouts.auth')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ showPassword: false }">
        @csrf

        <div class="form-group">
            <label for="username" class="block text-sm font-medium text-slate-700 mb-1">{{ __('Username') }}</label>
            <input id="username"
                   type="text"
                   name="username"
                   value="{{ old('username') }}"
                   required
                   autofocus
                   autocomplete="username"
                   class="form-control block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300 text-sm" />
            @error('username')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">{{ __('Password') }}</label>
            <div class="relative flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-1 focus-within:ring-slate-300 focus-within:border-slate-400">
                <input id="password"
                       :type="showPassword ? 'text' : 'password'"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="flex-1 min-w-0 border-0 bg-transparent px-3 py-2.5 pl-3 pr-11 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 text-sm" />
                <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-2 top-1/2 -translate-y-1/2 left-[auto] flex items-center justify-center w-8 h-8 rounded text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                        style="right: 0.5rem; left: auto;"
                        aria-label="{{ __('Show password') }}">
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878a4.5 4.5 0 106.262 6.262M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded-lg py-3 px-5 text-base font-semibold focus:outline-none focus:ring-2 focus:ring-slate-600 focus:ring-offset-1 shrink-0" style="background-color: #1e293b; color: #ffffff; margin-top: 0.5cm;">
            {{ __('Sign In') }}
        </button>
    </form>
@endsection
