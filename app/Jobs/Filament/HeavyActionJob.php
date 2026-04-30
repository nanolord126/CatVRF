<?php

declare(strict_types=1);

namespace App\Jobs\Filament;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use App\Services\Security\FilamentAuditService;

/**
 * Heavy Action Job for Filament
 *
 * Base job for heavy Filament actions that should run in background.
 * Includes audit logging, error handling, and user notifications.
 */
abstract class HeavyActionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum attempts for the job
     */
    public int $tries = 3;

    /**
     * Timeout in seconds
     */
    public int $timeout = 3600; // 1 hour

    /**
     * The user who initiated the action
     */
    protected readonly int $userId;

    /**
     * The tenant context
     */
    protected readonly ?int $tenantId;

    /**
     * Action name for logging
     */
    protected readonly string $actionName;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly LoggerInterface $logger,
        string $actionName,
        ?int $userId = null,
        ?int $tenantId = null,
        private readonly Guard $auth,
        private readonly LogManager $log,
        private readonly FilamentAuditService $auditService,) {
        $this->actionName = $actionName;
        $this->userId = $userId ?? (int) $this->auth->id();
        $this->tenantId = $tenantId ?? $this->auth->user()?->tenant_id;

        // Set queue for heavy actions
        $this->onQueue('bulk');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->log->$this->logger->info("Starting heavy Filament action: {$this->actionName}", [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
            ]);

            // Execute the actual action
            $result = $this->executeAction();

            // Log completion
            $this->logAction('completed', is_array($result) ? $result : []);

            // Send notification to user
            $this->notifyUser('completed', is_array($result) ? $result : []);

        } catch (\Exception $e) {
            $this->log->error("Heavy Filament action failed: {$this->actionName}", [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log failure
            $this->logAction('failed', ['error' => $e->getMessage()]);

            // Send notification to user
            $this->notifyUser('failed', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->log->error("Heavy Filament job failed permanently: {$this->actionName}", [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'exception' => $exception->getMessage(),
        ]);
    }

    /**
     * Execute the actual heavy action.
     * Override this method in child classes.
     */
    abstract protected function executeAction(): mixed;

    /**
     * Log the action to audit service.
     */
    protected function logAction(string $status, array $result = []): void
    {
        $this->auditService->logAction(
            action: "filament_heavy_{$this->actionName}_{$status}",
            changes: array_merge($result, [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'status' => $status,
            ])
        );
    }

    /**
     * Notify user about action status.
     */
    protected function notifyUser(string $status, array $result = []): void
    {
        // Implementation depends on notification system
        // Can use database notifications, email, or in-app notifications

        $notification = [
            'type' => $status === 'completed' ? 'success' : 'error',
            'title' => $status === 'completed'
                ? "Action completed: {$this->actionName}"
                : "Action failed: {$this->actionName}",
            'message' => $status === 'completed'
                ? "The {$this->actionName} action completed successfully."
                : "The {$this->actionName} action failed: ".($result['error'] ?? 'Unknown error'),
            'data' => $result,
        ];

        $this->log->$this->logger->info('User notification queued', [
            'user_id' => $this->userId,
            'notification' => $notification,
        ]);
    }
}
