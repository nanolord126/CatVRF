<?php

declare(strict_types=1);

namespace App\Domains\Audit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
use App\Domains\Audit\Models\AuditLog;

/**
 * AsyncAuditLogger — Queue job for async audit log persistence.
 * Production-ready with retries, timeout, and error handling.
 */
final class AsyncAuditLogger implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public string $queue = 'audit-logs';

    private readonly string $correlationId;

    public function __construct(private readonly array $payload)
    {
        $this->correlationId = $this->payload['correlation_id'] ?? Str::uuid()->toString();
    }

    public function tags(): array
    {
        return ['audit', 'log', ($this->payload['action'] ?? 'unknown')];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addMinutes(10);
    }

    public function handle(): void
    {
        try {
            // Apply field masking before storing
            $payload = $this->applyFieldMasking($this->payload);

            AuditLog::create(array_merge($payload, [
                'uuid' => Str::uuid(),
                'old_values' => $this->maskSensitiveData($payload['old_values'] ?? []),
                'new_values' => $this->maskSensitiveData($payload['new_values'] ?? []),
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ]));

            Log::channel('audit')->info('Audit log recorded', [
                'action' => $payload['action'],
                'subject_type' => $payload['subject_type'],
                'subject_id' => $payload['subject_id'],
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('security')->error('[AsyncAuditLogger] Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('security')->error('[AsyncAuditLogger] Failed permanently', [
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function applyFieldMasking(array $payload): array
    {
        $maskedFields = config('audit.masked_fields', [
            'password', 'password_confirmation', 'card_number', 'cvv',
            'token', 'api_key', 'secret', 'ssn', 'passport',
        ]);

        foreach (['old_values', 'new_values'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                foreach ($maskedFields as $field) {
                    if (isset($payload[$key][$field])) {
                        $payload[$key][$field] = str_repeat('*', strlen((string) $payload[$key][$field]));
                    }
                }
            }
        }

        return $payload;
    }

    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = config('audit.masked_fields', []);
        
        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = str_repeat('*', strlen((string) $data[$key]));
            }
        }

        return $data;
    }
}
