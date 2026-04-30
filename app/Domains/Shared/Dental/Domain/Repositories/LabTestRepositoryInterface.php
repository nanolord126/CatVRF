<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Repositories;

use Modules\Dental\Domain\Entities\LabTest;
use Modules\Dental\Domain\ValueObjects\LabTestId;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\TenantId;

interface LabTestRepositoryInterface
{
    public function save(LabTest $labTest): void;

    public function findById(LabTestId $id): ?LabTest;

    public function findByBarcode(string $barcode): ?LabTest;

    public function findByPatient(PatientId $patientId): array;

    public function findByTenant(TenantId $tenantId): array;

    public function delete(LabTestId $id): void;
}
