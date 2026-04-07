<?php

declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\EngineeringConsulting\Services;

use App\Domains\ConstructionAndRepair\EngineeringConsulting\Models\EngineeringProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EngineeringConsultingService
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
        int $consultantId,
        string $projectType,
        int $hoursSpent,
        string $dueDate,
        string $correlationId = '',
    ): EngineeringProject {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $guardId = $this->guard->id() ?? 0;

        if ($this->rateLimiter->tooManyAttempts('eng:proj:' . $guardId, 18)) {
            throw new \RuntimeException('Too many attempts', 429);
        }
        $this->rateLimiter->hit('eng:proj:' . $guardId, 3600);

        return $this->db->transaction(function () use ($consultantId, $projectType, $hoursSpent, $dueDate, $correlationId, $guardId) {
            $this->fraud->check(
                userId: $guardId,
                operationType: 'eng',
                amount: 0,
                correlationId: $correlationId,
            );

            $total = $hoursSpent * 450_00;
            $payoutKopecks = $total - (int) ($total * 0.14);

            $project = EngineeringProject::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => tenant()->id,
                'consultant_id' => $consultantId,
                'client_id' => $guardId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'project_type' => $projectType,
                'hours_spent' => $hoursSpent,
                'due_date' => $dueDate,
                'tags' => ['eng' => true],
            ]);

            $this->logger->info('Engineering project created', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()->id,
            ]);

            return $project;
        });
    }

    public function completeProject(int $projectId, string $correlationId = ''): EngineeringProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = EngineeringProject::findOrFail($projectId);

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
                reason: 'engineering_payout',
                correlationId: $correlationId,
            );

            $this->logger->info('Engineering project completed', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()->id,
            ]);

            return $project;
        });
    }

    public function cancelProject(int $projectId, string $correlationId = ''): EngineeringProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = EngineeringProject::findOrFail($projectId);

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
                    reason: 'engineering_refund',
                    correlationId: $correlationId,
                );
            }

            $this->logger->info('Engineering project cancelled', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()->id,
            ]);

            return $project;
        });
    }

    public function getProject(int $projectId): EngineeringProject
    {
        return EngineeringProject::findOrFail($projectId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, EngineeringProject>
     */
    public function getUserProjects(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return EngineeringProject::where('client_id', $clientId)
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
