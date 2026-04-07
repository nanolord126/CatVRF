<?php

declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\ArchitectureServices\Services;

use App\Domains\ConstructionAndRepair\ArchitectureServices\Models\ArchitectureProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class ArchitectureServicesService
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
        int $architectId,
        string $projectType,
        int $buildingSqm,
        string $dueDate,
        string $correlationId = '',
    ): ArchitectureProject {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $guardId = $this->guard->id() ?? 0;

        if ($this->rateLimiter->tooManyAttempts('arch:project:' . $guardId, 4)) {
            throw new \RuntimeException('Too many attempts', 429);
        }
        $this->rateLimiter->hit('arch:project:' . $guardId, 3600);

        return $this->db->transaction(function () use ($architectId, $projectType, $buildingSqm, $dueDate, $correlationId, $guardId) {
            $this->fraud->check(
                userId: $guardId,
                operationType: 'architecture',
                amount: 0,
                correlationId: $correlationId,
            );

            $total = $buildingSqm * 300_00;
            $payoutKopecks = $total - (int) ($total * 0.14);

            $project = ArchitectureProject::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => tenant()->id,
                'architect_id' => $architectId,
                'client_id' => $guardId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'project_type' => $projectType,
                'building_sqm' => $buildingSqm,
                'due_date' => $dueDate,
                'tags' => ['architecture' => true],
            ]);

            $this->logger->info('Architecture project created', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()->id,
            ]);

            return $project;
        });
    }

    public function completeProject(int $projectId, string $correlationId = ''): ArchitectureProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = ArchitectureProject::findOrFail($projectId);

            if ($project->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed', 400);
            }

            $project->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: (int) tenant()->id,
                amount: $project->payout_kopecks,
                reason: 'architecture_payout',
                correlationId: $correlationId,
            );

            $this->logger->info('Architecture project completed', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()->id,
            ]);

            return $project;
        });
    }

    public function cancelProject(int $projectId, string $correlationId = ''): ArchitectureProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = ArchitectureProject::findOrFail($projectId);

            if ($project->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed project', 400);
            }

            $previousPaymentStatus = $project->payment_status;

            $project->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousPaymentStatus === 'completed') {
                $this->wallet->credit(
                    walletId: (int) tenant()->id,
                    amount: $project->total_kopecks,
                    reason: 'architecture_refund',
                    correlationId: $correlationId,
                );
            }

            $this->logger->info('Architecture project cancelled', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()->id,
            ]);

            return $project;
        });
    }

    public function getProject(int $projectId): ArchitectureProject
    {
        return ArchitectureProject::findOrFail($projectId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ArchitectureProject>
     */
    public function getUserProjects(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return ArchitectureProject::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    private const VERSION = '1.0.0';

    private const MAX_RETRIES = 3;

    private const CACHE_TTL = 3600;

    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

    private function handleError(\Throwable $exception, int $attempt = 1): bool
    {
        if ($attempt >= self::MAX_RETRIES) {
            return false;
        }

        return true;
    }
}
