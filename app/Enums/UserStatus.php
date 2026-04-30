<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает верификации',
            self::Active => 'Активен',
            self::Suspended => 'Приостановлен',
            self::Banned => 'Заблокирован',
            self::Deleted => 'Удален',
        };
    }

    public function canLogin(): bool
    {
        return match ($this) {
            self::Active => true,
            default => false,
        };
    }

    public function canVerify(): bool
    {
        return match ($this) {
            self::Pending => true,
            default => false,
        };
    }

    public function isBlocked(): bool
    {
        return match ($this) {
            self::Suspended, self::Banned, self::Deleted => true,
            default => false,
        };
    }
    case Pending = 'pending';           // Registration initiated, verification pending
    case Active = 'active';             // Verified and active
    case Suspended = 'suspended';       // Suspended by admin/fraud
    case Banned = 'banned';             // Permanently banned
    case Deleted = 'deleted';           // Soft deleted
}
