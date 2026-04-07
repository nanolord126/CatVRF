<?php declare(strict_types=1);

namespace App\Domains\Content\VideoEditing\Services;

use App\Domains\Content\VideoEditing\Models\VideoEditor;
use App\Domains\Content\VideoEditing\Models\VideoProject;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class VideoEditingService
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
        int $editorId,
        string $projectType,
        int $editingHours,
        \DateTimeInterface|string $dueDate,
        string $correlationId = '',
    ): VideoProject {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();
        $rateLimiterKey = 'video:proj:' . ($this->guard->id() ?? 0);

        if ($this->rateLimiter->tooManyAttempts($rateLimiterKey, 10)) {
            throw new \RuntimeException('Too many requests', 429);
        }

        $this->rateLimiter->hit($rateLimiterKey, 3600);

        return $this->db->transaction(function () use ($editorId, $projectType, $editingHours, $dueDate, $correlationId): VideoProject {
            $editor = VideoEditor::findOrFail($editorId);
            $total = (int) ($editor->price_kopecks_per_hour * $editingHours);

            $fraudResult = $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'video_editing',
                amount: $total,
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Blocked by security', 403);
            }

            $project = VideoProject::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'editor_id' => $editorId,
                'client_id' => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'project_type' => $projectType,
                'editing_hours' => $editingHours,
                'due_date' => $dueDate,
                'tags' => ['video' => true],
            ]);

            $this->logger->info('Video project created', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function completeProject(int $projectId, string $correlationId = ''): VideoProject
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId): VideoProject {
            $project = VideoProject::findOrFail($projectId);

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

            $this->logger->info('Video project completed', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function cancelProject(int $projectId, string $correlationId = ''): VideoProject
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId): VideoProject {
            $project = VideoProject::findOrFail($projectId);

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

            $this->logger->info('Video project cancelled', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function getProject(int $projectId): VideoProject
    {
        return VideoProject::findOrFail($projectId);
    }

    public function getUserProjects(int $clientId): Collection
    {
        return VideoProject::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }
}
