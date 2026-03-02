<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PinVerifyController extends Controller
{
    /**
     * Show the PIN verification form (required after username/password login).
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($user->pin_set_at === null) {
            return redirect()->route('pin.setup');
        }
        if ($user->pin_locked_until && $user->pin_locked_until->isFuture()) {
            return redirect()->route('pin.verify')
                ->withErrors(['pin' => __('Too many failed attempts. Try again in :minutes minutes.', ['minutes' => (int) ceil($user->pin_locked_until->diffInSeconds(now()) / 60)])]);
        }
        return view('auth.pin-verify');
    }

    /**
     * Verify the security PIN and allow access.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ], [
            'pin.required' => __('Please enter your 6-digit security PIN.'),
            'pin.size' => __('The security PIN must be 6 digits.'),
            'pin.regex' => __('The security PIN must be 6 digits.'),
        ]);

        /** @var \App\Models\User $user */
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->pin_locked_until && $user->pin_locked_until->isFuture()) {
            return back()->withErrors(['pin' => __('Too many failed attempts. Please try again later.')]);
        }

        if (! Hash::check($request->input('pin'), $user->pin_hash)) {
            $attempts = $user->pin_attempts + 1;
            $updates = ['pin_attempts' => $attempts];
            if ($attempts >= 5) {
                $updates['pin_locked_until'] = now()->addMinutes(15);
            }
            $user->update($updates);

            return back()->withErrors(['pin' => __('Invalid security PIN.')]);
        }

        $user->update(['pin_attempts' => 0, 'pin_locked_until' => null]);
        $request->session()->put('pin_verified', true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
