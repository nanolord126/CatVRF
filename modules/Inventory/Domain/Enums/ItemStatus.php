<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum ItemStatus: string
{
    case ACTIVE = 'active';
    case EXPIRING_SOON = 'expiring_soon';
    case EXPIRED = 'expired';
    case QUARANTINE = 'quarantine';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активен',
            self::EXPIRING_SOON => 'Истекает срок',
            self::EXPIRED => 'Просрочен',
            self::QUARANTINE => 'Карантин',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::EXPIRING_SOON => 'warning',
            self::EXPIRED => 'danger',
            self::QUARANTINE => 'gray',
        };
    }
}
