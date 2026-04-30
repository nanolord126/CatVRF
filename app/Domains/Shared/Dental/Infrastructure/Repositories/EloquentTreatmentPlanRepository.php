<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Repositories;

use Modules\Dental\Domain\Entities\TreatmentPlan;
use Modules\Dental\Domain\Entities\TreatmentStep;
use Modules\Dental\Domain\Enums\TreatmentPlanStatus;
use Modules\Dental\Domain\Enums\TreatmentStepStatus;
use Modules\Dental\Domain\Repositories\TreatmentPlanRepositoryInterface;
use Modules\Dental\Domain\ValueObjects\TreatmentPlanId;
use Modules\Dental\Domain\ValueObjects\TreatmentStepId;
use Modules\Dental\Domain\ValueObjects\TreatmentStepCollection;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\Money;
use Modules\Dental\Infrastructure\Models\TreatmentPlanModel;
use Modules\Dental\Infrastructure\Models\TreatmentStepModel;
use Illuminate\Support\Facades\DB;

final readonly class EloquentTreatmentPlanRepository implements TreatmentPlanRepositoryInterface
{
    public function save(TreatmentPlan $treatmentPlan): void
    {
        DB::transaction(function () use ($treatmentPlan) {
            $model = TreatmentPlanModel::updateOrCreate(
                ['id' => $treatmentPlan->id->value],
                [
                    'patient_id' => $treatmentPlan->patientId->value,
                    'doctor_id' => $treatmentPlan->doctorId->value,
                    'tooth_chart_id' => $treatmentPlan->toothChartId?->value,
                    'tenant_id' => $treatmentPlan->tenantId->value,
                    'name' => $treatmentPlan->name,
                    'description' => $treatmentPlan->description,
                    'status' => $treatmentPlan->status->value,
                    'total_cost' => $treatmentPlan->totalCost->toDecimal(),
                    'discount_amount' => $treatmentPlan->discountAmount->toDecimal(),
                    'final_cost' => $treatmentPlan->finalCost->toDecimal(),
                    'start_date' => $treatmentPlan->startDate?->format('Y-m-d'),
                    'estimated_completion_date' => $treatmentPlan->estimatedCompletionDate?->format('Y-m-d'),
                    'actual_completion_date' => $treatmentPlan->actualCompletionDate?->format('Y-m-d'),
                    'metadata' => $treatmentPlan->metadata,
                ]
            );

            // Delete existing steps
            TreatmentStepModel::where('treatment_plan_id', $model->id)->delete();

            // Insert new steps
            foreach ($treatmentPlan->steps->all() as $step) {
                TreatmentStepModel::create([
                    'id' => $step->id->value,
                    'treatment_plan_id' => $model->id,
                    'name' => $step->name,
                    'description' => $step->description,
                    'tooth_number' => $step->toothNumber,
                    'status' => $step->status->value,
                    'cost' => $step->cost->toDecimal(),
                    'sort_order' => $step->sortOrder,
                    'scheduled_date' => $step->scheduledDate?->format('Y-m-d'),
                    'completed_date' => $step->completedDate?->format('Y-m-d'),
                    'performed_by' => $step->performedBy?->value,
                    'metadata' => $step->metadata,
                ]);
            }
        });
    }

    public function findById(TreatmentPlanId $id): ?TreatmentPlan
    {
        $model = TreatmentPlanModel::find($id->value);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByPatient(PatientId $patientId): array
    {
        $models = TreatmentPlanModel::forPatient($patientId->value)->get();
        return array_map([$this, 'modelToEntity'], $models->all());
    }

    public function findByTenant(TenantId $tenantId): array
    {
        $models = TreatmentPlanModel::forTenant($tenantId->value)->get();
        return array_map([$this, 'modelToEntity'], $models->all());
    }

    public function delete(TreatmentPlanId $id): void
    {
        TreatmentPlanModel::destroy($id->value);
    }

    private function modelToEntity(TreatmentPlanModel $model): TreatmentPlan
    {
        $stepsModels = TreatmentStepModel::forTreatmentPlan($model->id)->ordered()->get();
        $steps = new TreatmentStepCollection(
            array_map([$this, 'treatmentStepModelToEntity'], $stepsModels->all())
        );

        return new TreatmentPlan(
            id: TreatmentPlanId::fromString($model->id),
            patientId: PatientId::fromInt($model->patient_id),
            doctorId: DoctorId::fromInt($model->doctor_id),
            toothChartId: $model->tooth_chart_id ? ToothChartId::fromString($model->tooth_chart_id) : null,
            tenantId: TenantId::fromInt($model->tenant_id),
            name: $model->name,
            description: $model->description,
            status: TreatmentPlanStatus::from($model->status),
            totalCost: Money::fromDecimal((float) $model->total_cost),
            discountAmount: Money::fromDecimal((float) $model->discount_amount),
            finalCost: Money::fromDecimal((float) $model->final_cost),
            startDate: $model->start_date ? new \DateTimeImmutable($model->start_date) : null,
            estimatedCompletionDate: $model->estimated_completion_date ? new \DateTimeImmutable($model->estimated_completion_date) : null,
            actualCompletionDate: $model->actual_completion_date ? new \DateTimeImmutable($model->actual_completion_date) : null,
            steps: $steps,
            metadata: $model->metadata,
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at) : null,
            deletedAt: $model->deleted_at ? new \DateTimeImmutable($model->deleted_at) : null,
        );
    }

    private function treatmentStepModelToEntity(TreatmentStepModel $model): TreatmentStep
    {
        return new TreatmentStep(
            id: TreatmentStepId::fromString($model->id),
            treatmentPlanId: TreatmentPlanId::fromString($model->treatment_plan_id),
            name: $model->name,
            description: $model->description,
            toothNumber: $model->tooth_number,
            status: TreatmentStepStatus::from($model->status),
            cost: Money::fromDecimal((float) $model->cost),
            sortOrder: $model->sort_order,
            scheduledDate: $model->scheduled_date ? new \DateTimeImmutable($model->scheduled_date) : null,
            completedDate: $model->completed_date ? new \DateTimeImmutable($model->completed_date) : null,
            performedBy: $model->performed_by ? DoctorId::fromInt($model->performed_by) : null,
            metadata: $model->metadata,
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: new \DateTimeImmutable($model->updated_at),
        );
    }
}
