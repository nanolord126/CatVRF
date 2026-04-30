<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\CertificationId;
use Modules\CatCRM\Domain\Staff\ValueObjects\CertificationStatus;

/**
 * Certification — Сертификация сотрудника
 * 
 * Readonly DDD entity для представления сертификации
 */
final readonly class Certification
{
    public function __construct(
        public CertificationId $id,
        public int $tenantId,
        public int $employeeId,
        public string $name,
        public string $issuingOrganization,
        public string $certificateNumber,
        public CarbonImmutable $issueDate,
        public ?CarbonImmutable $expiryDate,
        public CertificationStatus $status,
        public ?string $credentialUrl,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function isValid(): bool
    {
        if ($this->expiryDate === null) {
            return $this->status === CertificationStatus::Active;
        }

        return $this->status === CertificationStatus::Active 
            && $this->expiryDate->isFuture();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate->lte(CarbonImmutable::now()->addDays($days));
    }
}
