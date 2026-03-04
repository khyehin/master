@extends('layouts.auth')

@section('content')
    <form method="POST" action="{{ route('password.first.store') }}" class="space-y-4">
        @csrf

        <div class="mb-4">
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Set your password') }}</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ __('For security, please set a new password for your first login.') }}
            </p>
        </div>

        <div class="form-group">
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">{{ __('New password') }}</label>
            <input id="password"
                   type="password"
                   name="password"
                   required
                   autocomplete="new-password"
                   class="form-control block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300 text-sm" />
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">{{ __('Confirm password') }}</label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   required
                   autocomplete="new-password"
                   class="form-control block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300 text-sm" />
        </div>

        <button type="submit" class="w-full rounded-lg py-3 px-5 text-base font-semibold focus:outline-none focus:ring-2 focus:ring-slate-600 focus:ring-offset-1 shrink-0" style="background-color: #1e293b; color: #ffffff; margin-top: 0.5cm;">
            {{ __('Save password') }}
        </button>
    </form>
@endsection

