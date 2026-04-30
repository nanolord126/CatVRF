<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Repositories;

use Modules\Dental\Domain\Entities\TreatmentPlan;
use Modules\Dental\Domain\ValueObjects\TreatmentPlanId;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\TenantId;

interface TreatmentPlanRepositoryInterface
{
    public function save(TreatmentPlan $treatmentPlan): void;

    public function findById(TreatmentPlanId $id): ?TreatmentPlan;

    public function findByPatient(PatientId $patientId): array;

    public function findByTenant(TenantId $tenantId): array;

    public function delete(TreatmentPlanId $id): void;
}
