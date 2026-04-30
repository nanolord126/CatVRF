<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

use Modules\BeautyMasters\Domain\Entities\CertificationLevel;
use Modules\BeautyMasters\Domain\Entities\CertificationStatus;
use Modules\BeautyMasters\Domain\Entities\CertificationType;

abstract readonly class BaseCertification
{
    public function __construct(
        public int $id,
        public int $masterId,
        public CertificationType $certificationType,
        public string $name,
        public ?string $issuer,
        public \DateTimeImmutable $issueDate,
        public ?\DateTimeImmutable $expiryDate,
        public ?string $certificateNumber,
        public ?string $documentFile,
        public CertificationStatus $status,
        public ?string $notes,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }

    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate < new \DateTimeImmutable();
    }

    public function isExpiringSoon(int $daysThreshold = 60): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        $threshold = new \DateTimeImmutable("+{$daysThreshold} days");
        return $this->expiryDate <= $threshold && $this->expiryDate > new \DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return $this->status === CertificationStatus::ACTIVE && !$this->isExpired();
    }
}
