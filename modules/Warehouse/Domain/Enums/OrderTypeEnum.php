<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Enums;

use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Log;

enum OrderTypeEnum: string
{
    case B2B = 'b2b';
    case B2C = 'b2c';

    public function getLabel(): string
    {
        return match ($this) {
            self::B2B => 'B2B Order',
            self::B2C => 'B2C Order',
        };
    }

    public function getPriority(): int
    {
        return match ($this) {
            self::B2B => 1,
            self::B2C => 2,
        };
    }

    public function requiresSpecialHandling(): bool
    {
        return $this === self::B2B;
    }

    public function getDefaultColor(): string
    {
        return match ($this) {
            self::B2B => '#4A5568',
            self::B2C => '#48BB78',
        };
    }
}
