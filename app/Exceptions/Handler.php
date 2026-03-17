<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use PDOException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (! $this->isDatabaseConnectionException($e)) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Servicio temporalmente no disponible. Contacte al administrador.',
                ], 503);
            }

            return response()->view('errors.database-unavailable', [], 503);
        });
    }

    /**
     * Determine if the exception was caused by an unavailable database connection.
     */
    protected function isDatabaseConnectionException(Throwable $exception): bool
    {
        $connectionErrorMarkers = [
            'access denied for user',
            'connection refused',
            'connection timed out',
            'getaddrinfo failed',
            'no connection could be made',
            'php_network_getaddresses',
            'server has gone away',
            'sqlstate[08001]',
            'sqlstate[08006]',
            'sqlstate[hy000] [1045]',
            'sqlstate[hy000] [2002]',
            'sqlstate[hy000] [2006]',
            'unknown database',
        ];

        do {
            if ($exception instanceof QueryException || $exception instanceof PDOException) {
                $message = strtolower($exception->getMessage());

                foreach ($connectionErrorMarkers as $marker) {
                    if (str_contains($message, $marker)) {
                        return true;
                    }
                }
            }
        } while ($exception = $exception->getPrevious());

        return false;
    }
}
