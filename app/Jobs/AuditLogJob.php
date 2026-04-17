<?php declare(strict_types=1);

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;



/**
 * Асинхронная запись аудит-логов в БД.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Используется в AuditService::record().
 * Очередь: audit-logs (Redis Horizon).
 */
/**
 * Class AuditLogJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs
 */
final class AuditLogJob implements ShouldQueue
{

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(private readonly array $payload,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(): void
    {
        try {
            $this->db->table('audit_logs')->insert(array_merge($this->payload, [
                'old_values' => json_encode($this->payload['old_values'] ?? [], JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode($this->payload['new_values'] ?? [], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        } catch (\Throwable $e) {
            $this->logger->channel('security')->error('AuditLogJob failed', [
                'error'          => $e->getMessage(),
                'correlation_id' => $this->payload['correlation_id'] ?? null,
            ]);

            $this->fail($e);
        }
    }
}
