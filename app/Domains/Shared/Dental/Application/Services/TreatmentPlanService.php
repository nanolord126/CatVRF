<?php

declare(strict_types=1);

namespace Modules\Dental\Application\Services;

use Modules\Dental\Domain\Entities\TreatmentPlan;
use Modules\Dental\Domain\Entities\TreatmentStep;
use Modules\Dental\Domain\Enums\TreatmentStepStatus;
use Modules\Dental\Domain\Repositories\TreatmentPlanRepositoryInterface;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\TreatmentPlanId;
use Modules\Dental\Domain\ValueObjects\TreatmentStepCollection;
use Modules\Dental\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\Log;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

final readonly class TreatmentPlanService
{
    use WithAuditLogging;

    public function __construct(
        private TreatmentPlanRepositoryInterface $treatmentPlanRepository,
        private readonly AuditService $auditService,
    ) {}

    public function createPlan(
        PatientId $patientId,
        DoctorId $doctorId,
        TenantId $tenantId,
        string $name,
        ?string $description = null,
        ?ToothChartId $toothChartId = null,
    ): TreatmentPlan {
        $plan = TreatmentPlan::create(
            patientId: $patientId,
            doctorId: $doctorId,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            toothChartId: $toothChartId,
        );

        $this->treatmentPlanRepository->save($plan);

        Log::info('Treatment plan created', [
            'plan_id' => $plan->id->value,
            'patient_id' => $patientId->value,
            'name' => $name,
        ]);

        return $plan;
    }

    public function addStep(
        TreatmentPlanId $planId,
        string $name,
        Money $cost,
        ?string $description = null,
        ?string $toothNumber = null,
        int $sortOrder = 0,
    ): TreatmentPlan {
        $plan = $this->treatmentPlanRepository->findById($planId);
        if ($plan === null) {
            throw new \InvalidArgumentException('Treatment plan not found');
        }

        $step = TreatmentStep::create(
            treatmentPlanId: $planId,
            name: $name,
            cost: $cost,
            description: $description,
            toothNumber: $toothNumber,
            sortOrder: $sortOrder,
        );

        $steps = $plan->steps->add($step);
        $updatedPlan = $plan->withSteps($steps);
        $this->treatmentPlanRepository->save($updatedPlan);

        Log::info('Treatment step added', [
            'plan_id' => $planId->value,
            'step_name' => $name,
        ]);

        return $updatedPlan;
    }

    public function activatePlan(TreatmentPlanId $planId): TreatmentPlan
    {
        $plan = $this->treatmentPlanRepository->findById($planId);
        if ($plan === null) {
            throw new \InvalidArgumentException('Treatment plan not found');
        }

        $updatedPlan = $plan->activate();
        $this->treatmentPlanRepository->save($updatedPlan);

        Log::info('Treatment plan activated', [
            'plan_id' => $planId->value,
        ]);

        return $updatedPlan;
    }

    public function scheduleStep(
        TreatmentPlanId $planId,
        string $stepId,
        \DateTimeImmutable $scheduledDate,
    ): TreatmentPlan {
        $plan = $this->treatmentPlanRepository->findById($planId);
        if ($plan === null) {
            throw new \InvalidArgumentException('Treatment plan not found');
        }

        $steps = new TreatmentStepCollection([]);
        foreach ($plan->steps->all() as $step) {
            if ($step->id->value === $stepId) {
                $step = $step->schedule($scheduledDate);
            }
            $steps = $steps->add($step);
        }

        $updatedPlan = $plan->withSteps($steps);
        $this->treatmentPlanRepository->save($updatedPlan);

        Log::info('Treatment step scheduled', [
            'plan_id' => $planId->value,
            'step_id' => $stepId,
            'scheduled_date' => $scheduledDate->format('Y-m-d'),
        ]);

        return $updatedPlan;
    }

    public function completeStep(
        TreatmentPlanId $planId,
        string $stepId,
        DoctorId $performedBy,
    ): TreatmentPlan {
        $plan = $this->treatmentPlanRepository->findById($planId);
        if ($plan === null) {
            throw new \InvalidArgumentException('Treatment plan not found');
        }

        $steps = new TreatmentStepCollection([]);
        foreach ($plan->steps->all() as $step) {
            if ($step->id->value === $stepId) {
                $step = $step->complete($performedBy);
            }
            $steps = $steps->add($step);
        }

        $updatedPlan = $plan->withSteps($steps);
        $this->treatmentPlanRepository->save($updatedPlan);

        Log::info('Treatment step completed', [
            'plan_id' => $planId->value,
            'step_id' => $stepId,
            'performed_by' => $performedBy->value,
        ]);

        return $updatedPlan;
    }

    public function applyDiscount(TreatmentPlanId $planId, Money $discountAmount): TreatmentPlan
    {
        $plan = $this->treatmentPlanRepository->findById($planId);
        if ($plan === null) {
            throw new \InvalidArgumentException('Treatment plan not found');
        }

        $updatedPlan = $plan->withDiscount($discountAmount);
        $this->treatmentPlanRepository->save($updatedPlan);

        Log::info('Discount applied to treatment plan', [
            'plan_id' => $planId->value,
            'discount_amount' => $discountAmount->toDecimal(),
        ]);

        return $updatedPlan;
    }

    public function completePlan(TreatmentPlanId $planId): TreatmentPlan
    {
        $plan = $this->treatmentPlanRepository->findById($planId);
        if ($plan === null) {
            throw new \InvalidArgumentException('Treatment plan not found');
        }

        $updatedPlan = $plan->markAsCompleted();
        $this->treatmentPlanRepository->save($updatedPlan);

        Log::info('Treatment plan completed', [
            'plan_id' => $planId->value,
        ]);

        return $updatedPlan;
    }

    public function getPlansForPatient(PatientId $patientId): array
    {
        return $this->treatmentPlanRepository->findByPatient($patientId);
    }

    public function getPlanById(TreatmentPlanId $planId): ?TreatmentPlan
    {
        return $this->treatmentPlanRepository->findById($planId);
    }
}
