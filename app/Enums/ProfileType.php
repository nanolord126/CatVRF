<?php

declare(strict_types=1);

namespace App\Enums;

enum ProfileType: string
{
    case Client = 'client';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Клиентский профиль (B2C)',
            self::Business => 'Бизнес-профиль (B2B/Tenant)',
        };
    }

    public function isClient(): bool
    {
        return $this === self::Client;
    }

    public function isBusiness(): bool
    {
        return $this === self::Business;
    }

    /**
     * Get opposite profile type (for transition checks)
     */
    public function opposite(): self
    {
        return match ($this) {
            self::Client => self::Business,
            self::Business => self::Client,
        };
    }
}
