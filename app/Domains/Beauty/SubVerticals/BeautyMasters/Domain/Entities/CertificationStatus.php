<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

enum CertificationStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case PENDING_VERIFICATION = 'pending_verification';
    case REVOKED = 'revoked';
    case SUSPENDED = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::REVOKED => 'Revoked',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function isValid(): bool
    {
        return $this === self::ACTIVE;
    }
}
