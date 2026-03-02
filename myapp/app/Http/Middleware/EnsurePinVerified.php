<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePinVerified
{
    /**
     * Require PIN setup or verification before accessing the application.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->pin_set_at === null) {
            return redirect()->route('pin.setup');
        }

        if (! $request->session()->get('pin_verified', false)) {
            return redirect()->route('pin.verify');
        }

        return $next($request);
    }
}
