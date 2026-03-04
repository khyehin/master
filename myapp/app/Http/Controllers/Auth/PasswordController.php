<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ]);

        return back()->with('status', 'password-updated');
    }

    /**
     * Show the "first login, reset password" form.
     */
    public function showFirst(Request $request): RedirectResponse|\Illuminate\View\View
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->password_changed_at !== null) {
            // Already changed; go to PIN flow
            if ($user->pin_set_at === null) {
                return redirect()->route('pin.setup');
            }
            return redirect()->route('pin.verify');
        }

        return view('auth.first-password');
    }

    /**
     * Handle first-login password reset (no current_password required).
     */
    public function storeFirst(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ]);

        // After setting password, go to PIN setup / verify
        if ($user->pin_set_at === null) {
            return redirect()->route('pin.setup')->with('status', __('Password set. Please set your PIN.'));
        }

        return redirect()->route('pin.verify')->with('status', __('Password set. Please verify your PIN.'));
    }
}
