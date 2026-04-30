<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Enums;

use App\Enums\BaseEnum;

/**
 * Loyalty Tier — Уровень лояльности клиента
 */
enum LoyaltyTier: string implements BaseEnum
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';
    case Diamond = 'diamond';

    public function label(): string
    {
        return match ($this) {
            self::Bronze => 'Бронза',
            self::Silver => 'Серебро',
            self::Gold => 'Золото',
            self::Platinum => 'Платина',
            self::Diamond => 'Алмаз',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bronze => 'amber',
            self::Silver => 'gray',
            self::Gold => 'yellow',
            self::Platinum => 'slate',
            self::Diamond => 'cyan',
        };
    }

    public function discountPercent(): int
    {
        return config("crm.loyalty_tiers.{$this->value}.discount_percent", 0);
    }

    public function minSpent(): int
    {
        return config("crm.loyalty_tiers.{$this->value}.min_spent", 0);
    }
}
