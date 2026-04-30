<?php

declare(strict_types=1);

namespace Modules\Dental\Application\Services;

use Modules\Dental\Domain\Entities\LabTest;
use Modules\Dental\Domain\Entities\LabResult;
use Modules\Dental\Domain\Enums\LabTestStatus;
use Modules\Dental\Domain\Repositories\LabTestRepositoryInterface;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\LabTestTypeId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\LabTestId;
use Modules\Dental\Domain\ValueObjects\LabResultCollection;
use Modules\Dental\Domain\ValueObjects\Money;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

final readonly class LabService
{
    use WithAuditLogging;

    public function __construct(
        private LabTestRepositoryInterface $labTestRepository,
        private readonly AuditService $auditService,
    ) {}

    public function orderTest(
        PatientId $patientId,
        DoctorId $doctorId,
        LabTestTypeId $labTestTypeId,
        TenantId $tenantId,
        Money $cost,
        ?string $notes = null,
    ): LabTest {
        $barcode = $this->generateBarcode();

        $labTest = LabTest::create(
            patientId: $patientId,
            doctorId: $doctorId,
            labTestTypeId: $labTestTypeId,
            tenantId: $tenantId,
            barcode: $barcode,
            cost: $cost,
            notes: $notes,
        );

        $this->labTestRepository->save($labTest);

        $this->logCreated(
            'LabTest',
            $labTest->id->value,
            $patientId->value,
            $tenantId->value,
            [
                'barcode' => $barcode,
                'doctor_id' => $doctorId->value,
                'cost' => $cost->amount,
            ]
        );

        return $labTest;
    }

    public function collectSample(LabTestId $labTestId): LabTest
    {
        $labTest = $this->labTestRepository->findById($labTestId);
        if ($labTest === null) {
            throw new \InvalidArgumentException('Lab test not found');
        }

        $updatedLabTest = $labTest->collectSample();
        $this->labTestRepository->save($updatedLabTest);

        $this->logAction('lab_sample_collected', 'LabTest', $labTestId->value, [
            'barcode' => $labTest->barcode,
        ], $labTest->patientId->value, $labTest->tenantId->value);

        return $updatedLabTest;
    }

    public function startProcessing(LabTestId $labTestId): LabTest
    {
        $labTest = $this->labTestRepository->findById($labTestId);
        if ($labTest === null) {
            throw new \InvalidArgumentException('Lab test not found');
        }

        $updatedLabTest = $labTest->startProcessing();
        $this->labTestRepository->save($updatedLabTest);

        $this->logAction('lab_processing_started', 'LabTest', $labTestId->value, [
            'barcode' => $labTest->barcode,
        ], $labTest->patientId->value, $labTest->tenantId->value);

        return $updatedLabTest;
    }

    public function addResult(
        LabTestId $labTestId,
        string $parameterName,
        string $parameterValue,
        ?string $unit = null,
        ?string $referenceRange = null,
        bool $isAbnormal = false,
        ?string $notes = null,
    ): LabTest {
        $labTest = $this->labTestRepository->findById($labTestId);
        if ($labTest === null) {
            throw new \InvalidArgumentException('Lab test not found');
        }

        $result = LabResult::create(
            labTestId: $labTestId,
            parameterName: $parameterName,
            parameterValue: $parameterValue,
            unit: $unit,
            referenceRange: $referenceRange,
            isAbnormal: $isAbnormal,
            notes: $notes,
        );

        $results = $labTest->results->add($result);
        $updatedLabTest = $labTest->withResults($results);
        $this->labTestRepository->save($updatedLabTest);

        $this->logAction('lab_result_added', 'LabTest', $labTestId->value, [
            'parameter_name' => $parameterName,
            'is_abnormal' => $isAbnormal,
        ], $labTest->patientId->value, $labTest->tenantId->value);

        return $updatedLabTest;
    }

    public function completeTest(LabTestId $labTestId): LabTest
    {
        $labTest = $this->labTestRepository->findById($labTestId);
        if ($labTest === null) {
            throw new \InvalidArgumentException('Lab test not found');
        }

        $updatedLabTest = $labTest->complete($labTest->results);
        $this->labTestRepository->save($updatedLabTest);

        $this->logAction('lab_test_completed', 'LabTest', $labTestId->value, [
            'barcode' => $labTest->barcode,
        ], $labTest->patientId->value, $labTest->tenantId->value);

        return $updatedLabTest;
    }

    public function getTestByBarcode(string $barcode): ?LabTest
    {
        return $this->labTestRepository->findByBarcode($barcode);
    }

    public function getTestsForPatient(PatientId $patientId): array
    {
        return $this->labTestRepository->findByPatient($patientId);
    }

    public function getTestById(LabTestId $labTestId): ?LabTest
    {
        return $this->labTestRepository->findById($labTestId);
    }

    private function generateBarcode(): string
    {
        return 'LAB-' . strtoupper(Str::random(12));
    }
}
