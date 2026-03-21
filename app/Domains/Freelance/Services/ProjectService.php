<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

final class ProjectService
{
    public function __construct()
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createProject'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createProject', ['domain' => __CLASS__]);

        try {
            $projectId = DB::transaction(function () use ($clientId, $freelancerId, $title, $budgetCents, $correlationId) {
                $projectId = DB::table('freelance_projects')->insertGetId([
                    'client_id' => $clientId,
                    'freelancer_id' => $freelancerId,
                    'title' => $title,
                    'budget' => $budgetCents,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('Freelance project created', [
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
            Log::channel('audit')->error('Freelance project creation failed', [
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'completeProject'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeProject', ['domain' => __CLASS__]);

        try {
            DB::transaction(function () use ($projectId, $correlationId) {
                $project = DB::table('freelance_projects')->findOrFail($projectId);

                // Обновить статус
                DB::table('freelance_projects')
                    ->where('id', $projectId)
                    ->update(['status' => 'completed', 'completed_at' => now()]);

                // Выплатить фрилансеру (минус комиссия платформы 14%)
                $commission = intval($project->budget * 0.14);
                $freelancerPayment = $project->budget - $commission;

                DB::table('freelancer_earnings')->insertGetId([
                    'freelancer_id' => $project->freelancer_id,
                    'project_id' => $projectId,
                    'amount' => $freelancerPayment,
                    'commission' => $commission,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('Freelance project completed', [
                    'project_id' => $projectId,
                    'freelancer_id' => $project->freelancer_id,
                    'payment' => $freelancerPayment,
                    'commission' => $commission,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Freelance project completion failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
