<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PinSetupController extends Controller
{
    /**
     * Show the form to set security PIN (first time only).
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($user->pin_set_at !== null) {
            return redirect()->route('pin.verify');
        }
        return view('auth.pin-setup');
    }

    /**
     * Store the new 6-digit security PIN.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/', 'confirmed'],
        ], [
            'pin.required' => __('Please enter a 6-digit security PIN.'),
            'pin.size' => __('The security PIN must be 6 digits.'),
            'pin.regex' => __('The security PIN must be 6 digits.'),
            'pin.confirmed' => __('The security PIN confirmation does not match.'),
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->pin_set_at !== null) {
            return redirect()->route('pin.verify');
        }

        $user->update([
            'pin_hash' => Hash::make($request->input('pin')),
            'pin_set_at' => now(),
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ]);

        $request->session()->put('pin_verified', true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
