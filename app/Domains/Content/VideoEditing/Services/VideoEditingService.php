<?php declare(strict_types=1);

namespace App\Domains\Content\VideoEditing\Services;

use App\Domains\Content\VideoEditing\Models\VideoEditor;
use App\Domains\Content\VideoEditing\Models\VideoProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class VideoEditingService extends Model
{
    use HasFactory;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
    ) {
    }

    public function createProject(
        int $editorId,
        string $projectType,
        int $editingHours,
        \DateTimeInterface|string $dueDate,
        string $correlationId = '',
    ): VideoProject {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();
        $rateLimiterKey = 'video:proj:' . (auth()->id() ?? 0);

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 10)) {
            throw new \RuntimeException('Too many', 429);
        }

        RateLimiter::hit($rateLimiterKey, 3600);

        return DB::transaction(function () use ($editorId, $projectType, $editingHours, $dueDate, $correlationId): VideoProject {
            $editor = VideoEditor::findOrFail($editorId);
            $total = (int) ($editor->price_kopecks_per_hour * $editingHours);

            $fraud = $this->fraud->check([
                'user_id' => auth()->id() ?? 0,
                'operation_type' => 'video_editing',
                'correlation_id' => $correlationId,
                'amount' => $total,
            ]);

            if ($fraud['decision'] === 'block') {
                throw new \RuntimeException('Security', 403);
            }

            $project = VideoProject::create([
                'uuid' => Str::uuid(),
                'tenant_id' => tenant()->id,
                'editor_id' => $editorId,
                'client_id' => auth()->id() ?? 0,
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

            Log::channel('audit')->info('Video project created', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function completeProject(int $projectId, string $correlationId = ''): VideoProject
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return DB::transaction(function () use ($projectId, $correlationId): VideoProject {
            $project = VideoProject::findOrFail($projectId);

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
                'video_payout',
                [
                    'correlation_id' => $correlationId,
                    'project_id' => $project->id,
                ],
            );

            Log::channel('audit')->info('Video project completed', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    public function cancelProject(int $projectId, string $correlationId = ''): VideoProject
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return DB::transaction(function () use ($projectId, $correlationId): VideoProject {
            $project = VideoProject::findOrFail($projectId);

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
                    'video_refund',
                    [
                        'correlation_id' => $correlationId,
                        'project_id' => $project->id,
                    ],
                );
            }

            Log::channel('audit')->info('Video project cancelled', [
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
