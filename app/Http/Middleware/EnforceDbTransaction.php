<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnforceDbTransaction extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
            return DB::transaction(function () use ($request, $next) {
                return $next($request);
            });
        }
}
