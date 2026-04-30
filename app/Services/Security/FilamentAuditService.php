<?php declare(strict_types=1);

namespace App\Services\Security;

use Psr\Log\LoggerInterface;

use Illuminate\Auth\AuthManager;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * Filament Audit Service
 * 
 * Logs all critical actions performed in Filament admin panels.
 * Tracks: user, tenant, action, model changes, IP, timestamp.
 * 
 * @package App\Services\Security
 */
final readonly class FilamentAuditService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly AuthManager $auth,
        private readonly LogManager $log,) {}

    private const string LOG_CHANNEL = 'filament-audit';

    /**
     * Log a Filament action
     */
    public function logAction(
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        array $changes = [],
        ?string $panel = null
    ): void {
        $user = $this->auth->user();
        
        $logData = [
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'action' => $action,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'tenant_id' => $user?->tenant_id ?? null,
            'panel' => $panel ?? $this->detectPanel(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'model_type' => $modelType,
            'model_id' => $modelId,
            'changes' => $this->sanitizeChanges($changes),
        ];

        $this->log->channel(self::LOG_CHANNEL)->$this->logger->info('filament_audit', $logData);
    }

    /**
     * Log model creation
     */
    public function logCreated(string $modelType, int $modelId, array $attributes = []): void
    {
        $this->logAction(
            action: 'created',
            modelType: $modelType,
            modelId: $modelId,
            changes: ['attributes' => $this->sanitizeData($attributes)]
        );
    }

    /**
     * Log model update
     */
    public function logUpdated(string $modelType, int $modelId, array $old, array $new): void
    {
        $changes = $this->getDiff($old, $new);
        
        $this->logAction(
            action: 'updated',
            modelType: $modelType,
            modelId: $modelId,
            changes: $changes
        );
    }

    /**
     * Log model deletion
     */
    public function logDeleted(string $modelType, int $modelId, array $attributes = []): void
    {
        $this->logAction(
            action: 'deleted',
            modelType: $modelType,
            modelId: $modelId,
            changes: ['deleted_attributes' => $this->sanitizeData($attributes)]
        );
    }

    /**
     * Log authentication event
     */
    public function logAuth(string $event, bool $success = true): void
    {
        $this->logAction(
            action: "auth_{$event}" . ($success ? '_success' : '_failed'),
            changes: ['success' => $success]
        );
    }

    /**
     * Detect current panel from request
     */
    private function detectPanel(): string
    {
        $path = request()->path();

        return match (true) {
            str_starts_with($path, 'admin') => 'landlord',
            str_starts_with($path, 'dashboard') => 'tenant',
            str_starts_with($path, 'b2b') => 'b2b',
            default => 'unknown',
        };
    }

    /**
     * Get diff between old and new values
     */
    private function getDiff(array $old, array $new): array
    {
        $diff = [];

        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] !== $value) {
                $diff[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        // Check for deleted keys
        foreach ($old as $key => $value) {
            if (!isset($new[$key])) {
                $diff[$key] = [
                    'old' => $value,
                    'new' => null,
                ];
            }
        }

        return $diff;
    }

    /**
     * Sanitize changes to remove sensitive data
     */
    private function sanitizeChanges(array $changes): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'api_key', 'credit_card'];

        foreach ($changes as $key => $value) {
            if (is_array($value)) {
                $changes[$key] = $this->sanitizeChanges($value);
            } elseif (is_string($value)) {
                foreach ($sensitiveKeys as $sensitive) {
                    if (str_contains(strtolower($key), $sensitive)) {
                        $changes[$key] = '[REDACTED]';
                        break;
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * Sanitize data for logging
     */
    private function sanitizeData(array $data): array
    {
        return $this->sanitizeChanges($data);
    }
}
