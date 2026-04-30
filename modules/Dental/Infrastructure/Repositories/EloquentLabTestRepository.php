<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Repositories;

use Modules\Dental\Domain\Entities\LabTest;
use Modules\Dental\Domain\Entities\LabResult;
use Modules\Dental\Domain\Enums\LabTestStatus;
use Modules\Dental\Domain\Repositories\LabTestRepositoryInterface;
use Modules\Dental\Domain\ValueObjects\LabTestId;
use Modules\Dental\Domain\ValueObjects\LabResultId;
use Modules\Dental\Domain\ValueObjects\LabResultCollection;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\LabTestTypeId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\Money;
use Modules\Dental\Infrastructure\Models\LabTestModel;
use Modules\Dental\Infrastructure\Models\LabResultModel;
use Illuminate\Support\Facades\DB;

final readonly class EloquentLabTestRepository implements LabTestRepositoryInterface
{
    public function save(LabTest $labTest): void
    {
        DB::transaction(function () use ($labTest) {
            $model = LabTestModel::updateOrCreate(
                ['id' => $labTest->id->value],
                [
                    'patient_id' => $labTest->patientId->value,
                    'doctor_id' => $labTest->doctorId->value,
                    'lab_test_type_id' => $labTest->labTestTypeId->value,
                    'tenant_id' => $labTest->tenantId->value,
                    'barcode' => $labTest->barcode,
                    'status' => $labTest->status->value,
                    'sample_collected_at' => $labTest->sampleCollectedAt?->format('Y-m-d H:i:s'),
                    'completed_at' => $labTest->completedAt?->format('Y-m-d H:i:s'),
                    'cost' => $labTest->cost->toDecimal(),
                    'notes' => $labTest->notes,
                    'metadata' => $labTest->metadata,
                ]
            );

            // Delete existing results
            LabResultModel::where('lab_test_id', $model->id)->delete();

            // Insert new results
            foreach ($labTest->results->all() as $result) {
                LabResultModel::create([
                    'id' => $result->id->value,
                    'lab_test_id' => $model->id,
                    'parameter_name' => $result->parameterName,
                    'parameter_value' => $result->parameterValue,
                    'unit' => $result->unit,
                    'reference_range' => $result->referenceRange,
                    'is_abnormal' => $result->isAbnormal,
                    'notes' => $result->notes,
                    'metadata' => $result->metadata,
                ]);
            }
        });
    }

    public function findById(LabTestId $id): ?LabTest
    {
        $model = LabTestModel::find($id->value);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByBarcode(string $barcode): ?LabTest
    {
        $model = LabTestModel::byBarcode($barcode)->first();
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByPatient(PatientId $patientId): array
    {
        $models = LabTestModel::forPatient($patientId->value)->get();
        return array_map([$this, 'modelToEntity'], $models->all());
    }

    public function findByTenant(TenantId $tenantId): array
    {
        $models = LabTestModel::forTenant($tenantId->value)->get();
        return array_map([$this, 'modelToEntity'], $models->all());
    }

    public function delete(LabTestId $id): void
    {
        LabTestModel::destroy($id->value);
    }

    private function modelToEntity(LabTestModel $model): LabTest
    {
        $resultsModels = LabResultModel::forLabTest($model->id)->get();
        $results = new LabResultCollection(
            array_map([$this, 'labResultModelToEntity'], $resultsModels->all())
        );

        return new LabTest(
            id: LabTestId::fromString($model->id),
            patientId: PatientId::fromInt($model->patient_id),
            doctorId: DoctorId::fromInt($model->doctor_id),
            labTestTypeId: LabTestTypeId::fromInt($model->lab_test_type_id),
            tenantId: TenantId::fromInt($model->tenant_id),
            barcode: $model->barcode,
            status: LabTestStatus::from($model->status),
            sampleCollectedAt: $model->sample_collected_at ? new \DateTimeImmutable($model->sample_collected_at) : null,
            completedAt: $model->completed_at ? new \DateTimeImmutable($model->completed_at) : null,
            cost: Money::fromDecimal((float) $model->cost),
            notes: $model->notes,
            results: $results,
            metadata: $model->metadata,
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at) : null,
            deletedAt: $model->deleted_at ? new \DateTimeImmutable($model->deleted_at) : null,
        );
    }

    private function labResultModelToEntity(LabResultModel $model): LabResult
    {
        return new LabResult(
            id: LabResultId::fromString($model->id),
            labTestId: LabTestId::fromString($model->lab_test_id),
            parameterName: $model->parameter_name,
            parameterValue: $model->parameter_value,
            unit: $model->unit,
            referenceRange: $model->reference_range,
            isAbnormal: $model->is_abnormal,
            notes: $model->notes,
            metadata: $model->metadata,
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: new \DateTimeImmutable($model->updated_at),
        );
    }
}
