<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleVerificationAttempts
{
    /**
     * The rate limiter instance.
     */
    protected RateLimiter $limiter;

    /**
     * Create a new middleware instance.
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Usar email del usuario como identificador
        $email = $request->user()?->email;
        
        if (!$email) {
            // Si no hay usuario autenticado, rechazar
            return response()->json([
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $key = 'verification-code-'.$email;

        // 5 intentos por 10 minutos (600 segundos)
        $maxAttempts = 5;
        $decaySeconds = 600; // 10 minutos

        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'message' => 'Demasiados intentos. Intenta de nuevo en '.$retryAfter.' segundos.',
                'retry_after' => $retryAfter,
                'blocked' => true,
            ], 429)->header('Retry-After', $retryAfter);
        }

        $this->limiter->hit($key, $decaySeconds);

        return $next($request);
    }
}
