<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class BaseDomainService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly AuditService $audit,
    ) {
        $this->correlationId = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();
    }

    /**
     * Wrapper for critical operations requiring transactions and logs.
     */
    protected function executeTransaction(callable $operation, string $actionName, int $amount = 0): mixed
    {
        $userId = $this->guard->id() ?? 0;

        $this->fraud->check(
            $userId,
            $actionName,
            $amount,
            $this->request->ip(),
            $this->request->header('User-Agent'),
            $this->correlationId
        );

        $this->logger->channel('audit')->$this->logger->info("Service: $actionName started", [
            'correlation_id' => $this->correlationId,
            'user_id' => $this->guard->id(),
            'tenant_id' => $this->guard->user()?->tenant_id,
        ]);

        try {
            return $this->db->transaction(function () use ($operation, $actionName) {
                $result = $operation($this->correlationId);

                if ($result === null) {
                    throw new RuntimeException("Action '$actionName' returned null. Null returns are forbidden in Canon 2026.");
                }

                return $result;
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error("Service: $actionName failed", [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            $this->logger->channel('audit')->$this->logger->info("Service: $actionName completed", [
                'correlation_id' => $this->correlationId,
            ]);
        }
    }
}
