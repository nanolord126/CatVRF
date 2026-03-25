declare(strict_types=1);

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceDbTransaction
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EnforceDbTransaction
{
    /**
     * Handle an incoming request.
     * Enforces DB transactions for all mutating HTTP requests (POST, PUT, PATCH, DELETE).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonMutating = ['GET', 'HEAD', 'OPTIONS'];

        if (in_array($request->method(), $nonMutating)) {
            return $next($request);
        }

        // For cross-database or multiple connection scenarios, this wraps the default connection
        return $this->db->transaction(function () use ($request, $next) {
            return $next($request);
        });
    }
}
