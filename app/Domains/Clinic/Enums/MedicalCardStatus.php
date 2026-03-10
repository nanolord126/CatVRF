<?php

namespace App\Domains\Clinic\Enums;

enum MedicalCardStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Активна',
            self::INACTIVE => 'Неактивна',
            self::ARCHIVED => 'В архиве',
            self::SUSPENDED => 'Приостановлена',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
