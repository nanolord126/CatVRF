<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Action2FAGuard {
    public function handle(Request $request, Closure $next) {
        $user = $request->user();
        
        if ($user && $user->two_factor_required && !$user->two_factor_confirmed_at) {
            return redirect()->route('two-factor.setup');
        }

        if ($request->isMethod('post') && $user->two_factor_enabled_at) {
            if (!$request->session()->has('2fa_verified_at')) {
                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }
}
