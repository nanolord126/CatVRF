<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApiKeyAuthentication extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Валидация API ключа для B2B интеграций.
         */
        public function handle(Request $request, Closure $next)
        {
            $apiKey = $request->header('X-API-Key');

            if (!$apiKey) {
                Log::channel('audit')->warning('API request without X-API-Key header', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ]);

                return response()->json([
                    'message' => 'API key is required',
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 401);
            }

            $keyHash = hash('sha256', $apiKey);
            $record = ApiKey::where('key_hash', $keyHash)
                ->where('revoked_at', null)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->with('tenant', 'user')
                ->first();

            if (!$record) {
                Log::channel('audit')->warning('Invalid or expired API key attempted', [
                    'key_preview' => substr($apiKey, 0, 10) . '...',
                    'ip' => $request->ip(),
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ]);

                return response()->json([
                    'message' => 'Invalid or expired API key',
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 401);
            }

            // Обновляем last_used_at
            $record->update(['last_used_at' => now()]);

            // Сохраняем в request для использования в контроллерах
            $request->merge([
                'api_key' => $record,
                'tenant' => $record->tenant,
                'user' => $record->user,
                'api_abilities' => json_decode($record->abilities, true) ?? [],
            ]);

            Log::channel('audit')->info('API key authenticated', [
                'key_id' => $record->id,
                'tenant_id' => $record->tenant_id,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return $next($request);
        }
}
