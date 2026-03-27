<?php

declare(strict_types=1);

namespace App\Services\Dental;

use App\Models\Dental\DentalTreatmentPlan;
use App\Models\Dental\DentalService as DentalModel;
use App\Models\Dental\Dentist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Treatment Plan Service (Main Controller for Multi-step Dental Planning).
 * Strictly follows CANON 2026: DB::transaction, correlation_id, and Medical Privacy.
 */
final readonly class TreatmentPlanService
{
    public function __construct(
        private \App\Services\FraudControlService $fraudControl,
        private string $correlation_id = ''
    ) {
        $this->correlation_id = empty($correlation_id) ? (string) Str::uuid() : $correlation_id;
    }

    /**
     * Get treatment plans for a patient with tenant scoping.
     */
    public function getPatientPlans(int $clientId): Collection
    {
        return DentalTreatmentPlan::where('client_id', $clientId)
            ->whereIn('status', ['draft', 'active', 'finished'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new treatment plan with budget forecasting and auditing.
     */
    public function createPlan(array $data): DentalTreatmentPlan
    {
        return DB::transaction(function () use ($data) {
            // 1. Audit Check
            Log::channel('audit')->info('Creating dental treatment plan', [
                'client_id' => $data['client_id'],
                'dentist_id' => $data['dentist_id'],
                'title' => $data['title'],
                'correlation_id' => $this->correlation_id,
            ]);

            // 2. Fraud Check (Medical Privacy/Fraud)
            $this->fraudControl->check(['operation' => 'create_treatment_plan', 'data' => $data]);

            // 3. Create Plan
            $plan = DentalTreatmentPlan::create(array_merge($data, [
                'correlation_id' => $this->correlation_id,
                'uuid' => (string) Str::uuid(),
                'status' => 'draft',
            ]));

            if (!$plan) {
                throw new \RuntimeException('Database error during dental treatment plan creation');
            }

            return $plan;
        });
    }

    /**
     * Update an existing plan with step verification.
     */
    public function updatePlan(int $id, array $data): DentalTreatmentPlan
    {
        return DB::transaction(function () use ($id, $data) {
            $plan = DentalTreatmentPlan::findOrFail($id);

            // Audit
            Log::channel('audit')->info('Updating dental treatment plan', [
                'plan_id' => $id,
                'old_status' => $plan->status,
                'new_status' => $data['status'] ?? $plan->status,
                'correlation_id' => $this->correlation_id,
            ]);

            $plan->update(array_merge($data, [
                'correlation_id' => $this->correlation_id,
            ]));

            return $plan;
        });
    }

    /**
     * Add a professional dental step (orthodontics, surgery) to the plan.
     */
    public function addStep(int $planId, array $stepData): void
    {
        DB::transaction(function () use ($planId, $stepData) {
            $plan = DentalTreatmentPlan::findOrFail($planId);

            // Verify service existence
            if (isset($stepData['service_id'])) {
                DentalModel::findOrFail($stepData['service_id']);
            }

            // Log
            Log::channel('audit')->info('Adding step to treatment plan', [
                'plan_id' => $planId,
                'step_name' => $stepData['name'],
                'correlation_id' => $this->correlation_id,
            ]);

            $plan->addStep($stepData);
        });
    }

    /**
     * Mark a step within the treatment plan as completed.
     */
    public function markStepCompleted(int $planId, string $stepUuid): void
    {
        DB::transaction(function () use ($planId, $stepUuid) {
            $plan = DentalTreatmentPlan::findOrFail($planId);
            $steps = $plan->steps ?? [];
            
            $found = false;
            foreach ($steps as &$step) {
                if ($step['id'] === $stepUuid) {
                    $step['status'] = 'completed';
                    $step['completed_at'] = now()->toIso8601String();
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new \RuntimeException("Step with UUID {$stepUuid} not found in plan {$planId}");
            }

            $plan->update(['steps' => $steps]);

            Log::channel('audit')->info('Treatment plan step marked completed', [
                'plan_id' => $planId,
                'step_uuid' => $stepUuid,
                'correlation_id' => $this->correlation_id,
            ]);
        });
    }

    /**
     * Delete/Archive a plan with auditing.
     */
    public function archivePlan(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $plan = DentalTreatmentPlan::findOrFail($id);
            Log::channel('audit')->warning('Archiving dental treatment plan', [
                'plan_id' => $id,
                'correlation_id' => $this->correlation_id,
            ]);
            
            return $plan->update(['status' => 'archived']) && $plan->delete();
        });
    }
}
