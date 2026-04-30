<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum IoTSecurityStatus: string
{
    case ACTIVE = 'active';
    case QUARANTINED = 'quarantined';
    case COMPROMISED = 'compromised';
    case DISABLED = 'disabled';

    public function canReceiveCommands(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canSendTelemetry(): bool
    {
        return match ($this) {
            self::ACTIVE, self::QUARANTINED => true,
            self::COMPROMISED, self::DISABLED => false,
        };
    }

    public function requiresAdminIntervention(): bool
    {
        return match ($this) {
            self::COMPROMISED, self::DISABLED => true,
            self::ACTIVE, self::QUARANTINED => false,
        };
    }
}
