<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\TuningProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Finances\Services\Security\FraudControlService;

final class TuningService
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {}

    public function createProject(array $data): TuningProject
    {
        $correlationId = Str::uuid()->toString();

        $this->log->channel('audit')->info('Creating tuning project', [
            'correlation_id' => $correlationId,
            'tenant_id' => tenant()->id,
        ]);

        try {
            $this->fraudControl->check('tuning_project_creation', request()->ip(), [
                'user_id' => auth()->id(),
                'amount' => $data['estimated_price'] ?? 0,
            ]);

            $project = $this->db->transaction(function () use ($data, $correlationId) {
                return TuningProject::create([
                    ...$data,
                    'tenant_id' => tenant()->id,
                    'status' => 'pending',
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->log->channel('audit')->info('Tuning project created', [
                'correlation_id' => $correlationId,
                'project_id' => $project->id,
            ]);

            return $project;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Tuning project creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function updateProgress(int $projectId, string $status, ?array $completedStages = null): TuningProject
    {
        $correlationId = Str::uuid()->toString();

        try {
            $project = TuningProject::findOrFail($projectId);

            $this->db->transaction(function () use ($project, $status, $completedStages) {
                $updateData = ['status' => $status];

                if ($completedStages !== null) {
                    $updateData['completed_stages'] = $completedStages;
                }

                if ($status === 'completed') {
                    $updateData['completed_at'] = now();
                }

                $project->update($updateData);
            });

            $this->log->channel('audit')->info('Tuning project progress updated', [
                'correlation_id' => $correlationId,
                'project_id' => $projectId,
                'status' => $status,
            ]);

            return $project->fresh();
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Tuning project update failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
