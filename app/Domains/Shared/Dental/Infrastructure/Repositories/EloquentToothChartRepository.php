<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Repositories;

use Modules\Dental\Domain\Entities\ToothChart;
use Modules\Dental\Domain\Entities\ToothStatus;
use Modules\Dental\Domain\Enums\ToothStatus as ToothStatusEnum;
use Modules\Dental\Domain\Repositories\ToothChartRepositoryInterface;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\ToothStatusId;
use Modules\Dental\Domain\ValueObjects\ToothStatusCollection;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\NumberingSystem;
use Modules\Dental\Infrastructure\Models\ToothChartModel;
use Modules\Dental\Infrastructure\Models\ToothStatusModel;
use Illuminate\Support\Facades\DB;

final readonly class EloquentToothChartRepository implements ToothChartRepositoryInterface
{
    public function save(ToothChart $toothChart): void
    {
        DB::transaction(function () use ($toothChart) {
            $model = ToothChartModel::updateOrCreate(
                ['id' => $toothChart->id->value],
                [
                    'patient_id' => $toothChart->patientId->value,
                    'doctor_id' => $toothChart->doctorId?->value,
                    'tenant_id' => $toothChart->tenantId->value,
                    'numbering_system' => $toothChart->numberingSystem->value,
                    'is_primary' => $toothChart->isPrimary,
                    'metadata' => $toothChart->metadata,
                ]
            );

            // Delete existing teeth statuses
            ToothStatusModel::where('tooth_chart_id', $model->id)->delete();

            // Insert new teeth statuses
            foreach ($toothChart->teeth->all() as $tooth) {
                ToothStatusModel::create([
                    'id' => $tooth->id->value,
                    'tooth_chart_id' => $model->id,
                    'tooth_number' => $tooth->toothNumber,
                    'status' => $tooth->status->value,
                    'color' => $tooth->color,
                    'description' => $tooth->description,
                    'notes' => $tooth->notes,
                    'performed_by' => $tooth->performedBy?->value,
                    'performed_at' => $tooth->performedAt?->format('Y-m-d H:i:s'),
                    'metadata' => $tooth->metadata,
                ]);
            }
        });
    }

    public function findById(ToothChartId $id): ?ToothChart
    {
        $model = ToothChartModel::find($id->value);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByPatient(PatientId $patientId, bool $isPrimary = true): ?ToothChart
    {
        $model = ToothChartModel::forPatient($patientId->value)
            ->where('is_primary', $isPrimary)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByTenant(TenantId $tenantId): array
    {
        $models = ToothChartModel::forTenant($tenantId->value)->get();
        return array_map([$this, 'modelToEntity'], $models->all());
    }

    public function delete(ToothChartId $id): void
    {
        ToothChartModel::destroy($id->value);
    }

    private function modelToEntity(ToothChartModel $model): ToothChart
    {
        $teethModels = ToothStatusModel::forToothChart($model->id)->get();
        $teeth = new ToothStatusCollection(
            array_map([$this, 'toothStatusModelToEntity'], $teethModels->all())
        );

        return new ToothChart(
            id: ToothChartId::fromString($model->id),
            patientId: PatientId::fromInt($model->patient_id),
            doctorId: $model->doctor_id ? DoctorId::fromInt($model->doctor_id) : null,
            tenantId: TenantId::fromInt($model->tenant_id),
            numberingSystem: NumberingSystem::from($model->numbering_system),
            isPrimary: $model->is_primary,
            teeth: $teeth,
            metadata: $model->metadata,
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at) : null,
            deletedAt: $model->deleted_at ? new \DateTimeImmutable($model->deleted_at) : null,
        );
    }

    private function toothStatusModelToEntity(ToothStatusModel $model): ToothStatus
    {
        return new ToothStatus(
            id: ToothStatusId::fromString($model->id),
            toothChartId: ToothChartId::fromString($model->tooth_chart_id),
            toothNumber: $model->tooth_number,
            status: ToothStatusEnum::from($model->status),
            color: $model->color,
            description: $model->description,
            notes: $model->notes,
            performedBy: $model->performed_by ? DoctorId::fromInt($model->performed_by) : null,
            performedAt: $model->performed_at ? new \DateTimeImmutable($model->performed_at) : null,
            metadata: $model->metadata,
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: new \DateTimeImmutable($model->updated_at),
        );
    }
}
