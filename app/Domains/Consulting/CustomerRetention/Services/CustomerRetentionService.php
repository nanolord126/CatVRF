<?php declare(strict_types=1);

namespace App\Domains\Consulting\CustomerRetention\Services;

use App\Domains\Consulting\CustomerRetention\Models\RetentionProgram;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class CustomerRetentionService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private RateLimiter $rateLimiter,
    ) {}

    public function createProject(
        int $providerId,
        string $serviceType,
        string $correlationId = '',
    ): RetentionProgram {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();
        $rateLimiterKey = 'customer_retention:create:' . ($this->guard->id() ?? 0);

        if ($this->rateLimiter->tooManyAttempts($rateLimiterKey, 15)) {
            throw new \RuntimeException('Too many requests', 429);
        }

        $this->rateLimiter->hit($rateLimiterKey, 3600);

        return $this->db->transaction(function () use ($providerId, $serviceType, $correlationId): RetentionProgram {
            $fraudResult = $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'customer_retention_create',
                amount: 0,
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Blocked by security', 403);
            }

            $project = RetentionProgram::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'provider_id' => $providerId,
                'client_id' => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => 0,
                'payout_kopecks' => 0,
                'payment_status' => 'pending',
                'service_type' => $serviceType,
                'tags' => ['customerretention' => true],
            ]);

            $this->logger->info('CustomerRetentionService: project created', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function completeProject(int $projectId, string $correlationId = ''): RetentionProgram
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId): RetentionProgram {
            $project = RetentionProgram::findOrFail($projectId);

            if ($project->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $project->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: (int) tenant()->id,
                amount: $project->payout_kopecks,
                reason: 'consulting_payout',
                correlationId: $correlationId,
            );

            $this->logger->info('CustomerRetentionService: project completed', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function cancelProject(int $projectId, string $correlationId = ''): RetentionProgram
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId): RetentionProgram {
            $project = RetentionProgram::findOrFail($projectId);

            if ($project->status === 'completed') {
                throw new \RuntimeException('Cannot cancel a completed project', 400);
            }

            $project->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($project->payment_status === 'completed') {
                $this->wallet->credit(
                    walletId: (int) tenant()->id,
                    amount: $project->total_kopecks,
                    reason: 'consulting_refund',
                    correlationId: $correlationId,
                );
            }

            $this->logger->info('CustomerRetentionService: project cancelled', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function getProject(int $projectId): RetentionProgram
    {
        return RetentionProgram::findOrFail($projectId);
    }

    public function getUserProjects(int $clientId): Collection
    {
        return RetentionProgram::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }
}
