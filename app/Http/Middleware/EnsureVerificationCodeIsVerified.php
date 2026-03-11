<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerificationCodeIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $isVerified = (bool) $request->session()->get('code_verified', false);
        $verifiedUserId = (int) $request->session()->get('code_verified_user_id', 0);

        if ($isVerified && $verifiedUserId === (int) $user->id) {
            return $next($request);
        }

        return redirect()->route('code-verification.notice');
    }
}
