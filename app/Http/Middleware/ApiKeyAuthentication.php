<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ApiKeyAuthentication
{
    /**
     * Валидация API ключа для B2B интеграций.
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            $this->log->channel('audit')->warning('API request without X-API-Key header', [
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
            $this->log->channel('audit')->warning('Invalid or expired API key attempted', [
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

        $this->log->channel('audit')->info('API key authenticated', [
            'key_id' => $record->id,
            'tenant_id' => $record->tenant_id,
            'correlation_id' => $request->header('X-Correlation-ID'),
        ]);

        return $next($request);
    }
}
