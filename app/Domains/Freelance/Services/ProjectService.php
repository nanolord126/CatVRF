<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use Illuminate\Support\Facades\DB;

final class ProjectService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,)
    {
    }

    /**
     * Создать проект (заказ)
     */
    public function createProject(
        int $clientId,
        int $freelancerId,
        string $title,
        int $budgetCents,
        string $correlationId,
    ): int {


        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $projectId = $this->db->transaction(function () use ($clientId, $freelancerId, $title, $budgetCents, $correlationId) {
                $projectId = $this->db->table('freelance_projects')->insertGetId([
                    'client_id' => $clientId,
                    'freelancer_id' => $freelancerId,
                    'title' => $title,
                    'budget' => $budgetCents,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                $this->log->channel('audit')->info('Freelance project created', [
                    'project_id' => $projectId,
                    'client_id' => $clientId,
                    'freelancer_id' => $freelancerId,
                    'budget' => $budgetCents,
                    'correlation_id' => $correlationId,
                ]);

                return $projectId;
            });

            return $projectId;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Freelance project creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Завершить проект (escrow -> выплата фрилансеру)
     */
    public function completeProject(int $projectId, string $correlationId): bool
    {


        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $this->db->transaction(function () use ($projectId, $correlationId) {
                $project = $this->db->table('freelance_projects')->findOrFail($projectId);

                // Обновить статус
                $this->db->table('freelance_projects')
                    ->where('id', $projectId)
                    ->update(['status' => 'completed', 'completed_at' => now()]);

                // Выплатить фрилансеру (минус комиссия платформы 14%)
                $commission = intval($project->budget * 0.14);
                $freelancerPayment = $project->budget - $commission;

                $this->db->table('freelancer_earnings')->insertGetId([
                    'freelancer_id' => $project->freelancer_id,
                    'project_id' => $projectId,
                    'amount' => $freelancerPayment,
                    'commission' => $commission,
                    'created_at' => now(),
                ]);

                $this->log->channel('audit')->info('Freelance project completed', [
                    'project_id' => $projectId,
                    'freelancer_id' => $project->freelancer_id,
                    'payment' => $freelancerPayment,
                    'commission' => $commission,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Freelance project completion failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
