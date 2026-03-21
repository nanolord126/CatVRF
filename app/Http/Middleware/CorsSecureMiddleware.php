<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class CorsSecureMiddleware
{
    /**
     * Обработать входящий запрос с CORS-валидацией.
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = explode(',', config('app.cors_allowed_origins', 'http://localhost:3000'));
        $origin = $request->header('Origin');

        if (!$origin || !in_array($origin, $allowedOrigins, true)) {
            if ($request->isMethod('OPTIONS')) {
                return response()->noContent(204);
            }
        }

        $response = $next($request);

        if ($origin && in_array($origin, $allowedOrigins, true)) {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS,PATCH');
            $response->header('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Correlation-ID,X-Idempotency-Key');
            $response->header('Access-Control-Allow-Credentials', 'true');
            $response->header('Access-Control-Max-Age', '3600');
        }

        return $response;
    }
}
