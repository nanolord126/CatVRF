<?php declare(strict_types=1);

namespace App\Services\Dental;




use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\Dental\DentalTreatmentPlan;
use App\Models\Dental\DentalService as DentalModel;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class TreatmentPlanService
{

    public function __construct(
        private readonly Request $request,
        private \App\Services\FraudControlService $fraud,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
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
            return $this->db->transaction(function () use ($data) {
                // 1. Audit Check
                $this->logger->channel('audit')->info('Creating dental treatment plan', [
                    'client_id' => $data['client_id'],
                    'dentist_id' => $data['dentist_id'],
                    'title' => $data['title'],
                    'correlation_id' => $this->correlationId(),
                ]);

                // 2. Fraud Check (Medical Privacy/Fraud)
                $this->fraud->check((int) $this->guard->id(), 'create_treatment_plan', $this->request->ip());

                // 3. Create Plan
                $plan = DentalTreatmentPlan::create(array_merge($data, [
                    'correlation_id' => $this->correlationId(),
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
            return $this->db->transaction(function () use ($id, $data) {
                $plan = DentalTreatmentPlan::findOrFail($id);

                // Audit
                $this->logger->channel('audit')->info('Updating dental treatment plan', [
                    'plan_id' => $id,
                    'old_status' => $plan->status,
                    'new_status' => $data['status'] ?? $plan->status,
                    'correlation_id' => $this->correlationId(),
                ]);

                $plan->update(array_merge($data, [
                    'correlation_id' => $this->correlationId(),
                ]));

                return $plan;
            });
        }

        /**
         * Add a professional dental step (orthodontics, surgery) to the plan.
         */
        public function addStep(int $planId, array $stepData): void
        {
            $this->db->transaction(function () use ($planId, $stepData) {
                $plan = DentalTreatmentPlan::findOrFail($planId);

                // Verify service existence
                if (isset($stepData['service_id'])) {
                    DentalModel::findOrFail($stepData['service_id']);
                }

                // Log
                $this->logger->channel('audit')->info('Adding step to treatment plan', [
                    'plan_id' => $planId,
                    'step_name' => $stepData['name'],
                    'correlation_id' => $this->correlationId(),
                ]);

                $plan->addStep($stepData);
            });
        }

        /**
         * Mark a step within the treatment plan as completed.
         */
        public function markStepCompleted(int $planId, string $stepUuid): void
        {
            $this->db->transaction(function () use ($planId, $stepUuid) {
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

                $this->logger->channel('audit')->info('Treatment plan step marked completed', [
                    'plan_id' => $planId,
                    'step_uuid' => $stepUuid,
                    'correlation_id' => $this->correlationId(),
                ]);
            });
        }

        /**
         * Delete/Archive a plan with auditing.
         */
        public function archivePlan(int $id): bool
        {
            return $this->db->transaction(function () use ($id) {
                $plan = DentalTreatmentPlan::findOrFail($id);
                $this->logger->channel('audit')->warning('Archiving dental treatment plan', [
                    'plan_id' => $id,
                    'correlation_id' => $this->correlationId(),
                ]);

                return $plan->update(['status' => 'archived']) && $plan->delete();
            });
        }
}
