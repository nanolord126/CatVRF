<?php declare(strict_types=1);

namespace App\Domains\Content\PodcastProduction\Services;

use App\Domains\Content\PodcastProduction\Models\PodcastProducer;
use App\Domains\Content\PodcastProduction\Models\PodcastProject;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class PodcastProductionService
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
        int $producerId,
        string $projectType,
        int $productionHours,
        \DateTimeInterface|string $dueDate,
        string $correlationId = '',
    ): PodcastProject {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();
        $rateLimiterKey = 'podcast:proj:' . ($this->guard->id() ?? 0);

        if ($this->rateLimiter->tooManyAttempts($rateLimiterKey, 7)) {
            throw new \RuntimeException('Too many requests', 429);
        }

        $this->rateLimiter->hit($rateLimiterKey, 3600);

        return $this->db->transaction(function () use ($producerId, $projectType, $productionHours, $dueDate, $correlationId): PodcastProject {
            $producer = PodcastProducer::findOrFail($producerId);
            $total = (int) ($producer->price_kopecks_per_hour * $productionHours);

            $fraudResult = $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'podcast_prod',
                amount: $total,
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Blocked by security', 403);
            }

            $project = PodcastProject::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'producer_id' => $producerId,
                'client_id' => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'project_type' => $projectType,
                'production_hours' => $productionHours,
                'due_date' => $dueDate,
                'tags' => ['podcast' => true],
            ]);

            $this->logger->info('Podcast project created', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function completeProject(int $projectId, string $correlationId = ''): PodcastProject
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId): PodcastProject {
            $project = PodcastProject::findOrFail($projectId);

            if ($project->payment_status !== 'completed') {
                throw new \RuntimeException('Project not paid yet', 400);
            }

            $project->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $project->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: [
                    'project_id' => $project->id,
                    'correlation_id' => $correlationId,
                ],
            );

            $this->logger->info('Podcast project completed', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function cancelProject(int $projectId, string $correlationId = ''): PodcastProject
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId): PodcastProject {
            $project = PodcastProject::findOrFail($projectId);

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
                    walletId: tenant()->id,
                    amount: $project->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: [
                        'project_id' => $project->id,
                        'correlation_id' => $correlationId,
                    ],
                );
            }

            $this->logger->info('Podcast project cancelled', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function getProject(int $projectId): PodcastProject
    {
        return PodcastProject::findOrFail($projectId);
    }

    public function getUserProjects(int $clientId): Collection
    {
        return PodcastProject::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }
}
