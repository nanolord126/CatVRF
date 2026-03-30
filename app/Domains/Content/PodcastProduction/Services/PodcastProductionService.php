<?php declare(strict_types=1);

namespace App\Domains\Content\PodcastProduction\Services;

use App\Domains\Content\PodcastProduction\Models\PodcastProducer;
use App\Domains\Content\PodcastProduction\Models\PodcastProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class PodcastProductionService extends Model
{
    use HasFactory;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
    ) {
    }

    public function createProject(
        int $producerId,
        string $projectType,
        int $productionHours,
        \DateTimeInterface|string $dueDate,
        string $correlationId = '',
    ): PodcastProject {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();
        $rateLimiterKey = 'podcast:proj:' . (auth()->id() ?? 0);

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 7)) {
            throw new \RuntimeException('Too many', 429);
        }

        RateLimiter::hit($rateLimiterKey, 3600);

        return DB::transaction(function () use ($producerId, $projectType, $productionHours, $dueDate, $correlationId): PodcastProject {
            $producer = PodcastProducer::findOrFail($producerId);
            $total = (int) ($producer->price_kopecks_per_hour * $productionHours);

            $fraud = $this->fraud->check([
                'user_id' => auth()->id() ?? 0,
                'operation_type' => 'podcast_prod',
                'correlation_id' => $correlationId,
                'amount' => $total,
            ]);

            if ($fraud['decision'] === 'block') {
                throw new \RuntimeException('Security', 403);
            }

            $project = PodcastProject::create([
                'uuid' => Str::uuid(),
                'tenant_id' => tenant()->id,
                'producer_id' => $producerId,
                'client_id' => auth()->id() ?? 0,
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

            Log::channel('audit')->info('Podcast project created', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function completeProject(int $projectId, string $correlationId = ''): PodcastProject
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return DB::transaction(function () use ($projectId, $correlationId): PodcastProject {
            $project = PodcastProject::findOrFail($projectId);

            if ($project->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $project->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                tenant()->id,
                $project->payout_kopecks,
                'podcast_payout',
                [
                    'correlation_id' => $correlationId,
                    'project_id' => $project->id,
                ],
            );

            Log::channel('audit')->info('Podcast project completed', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function cancelProject(int $projectId, string $correlationId = ''): PodcastProject
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return DB::transaction(function () use ($projectId, $correlationId): PodcastProject {
            $project = PodcastProject::findOrFail($projectId);

            if ($project->status === 'completed') {
                throw new \RuntimeException('Cannot cancel', 400);
            }

            $project->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($project->payment_status === 'completed') {
                $this->wallet->credit(
                    tenant()->id,
                    $project->total_kopecks,
                    'podcast_refund',
                    [
                        'correlation_id' => $correlationId,
                        'project_id' => $project->id,
                    ],
                );
            }

            Log::channel('audit')->info('Podcast project cancelled', [
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
