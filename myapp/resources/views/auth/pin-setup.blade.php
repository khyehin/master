@extends('layouts.auth')

@section('content')
    <h1 class="text-lg font-semibold text-black mb-2">{{ __('Set Security PIN') }}</h1>
    <p class="mb-6 text-sm text-black">{{ __('Set a 6-digit security PIN. You will need it every time you log in.') }}</p>

    <form id="pin-setup-form" method="POST" action="{{ route('pin.setup') }}">
        @csrf

        <div>
            <label for="pin" class="block font-medium text-sm text-black mb-1">{{ __('Security PIN') }}</label>
            <input id="pin" class="block mt-1 w-full rounded border-2 border-black shadow-sm focus:border-black focus:ring-black" type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="off" required autofocus placeholder="000000" />
            @error('pin')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-4">
            <label for="pin_confirmation" class="block font-medium text-sm text-black mb-1">{{ __('Confirm Security PIN') }}</label>
            <input id="pin_confirmation" class="block mt-1 w-full rounded border-2 border-black shadow-sm focus:border-black focus:ring-black" type="password" name="pin_confirmation" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="off" required placeholder="000000" />
        </div>
    </form>

    <div class="flex items-center justify-between mt-6">
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm text-black underline hover:no-underline">
                {{ __('Cancel / Log out') }}
            </button>
        </form>
        <button type="submit" form="pin-setup-form" class="rounded border-2 border-black bg-gray-300 px-4 py-2 text-sm font-semibold text-black hover:bg-gray-400">
            {{ __('Set PIN') }}
        </button>
    </div>
@endsection
