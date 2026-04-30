<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum InventoryCategory: string
{
    case MEDICATION = 'medication';
    case FEED = 'feed';
    case GROOMING_PRODUCT = 'grooming_product';
    case KITCHEN_PRODUCT = 'kitchen_product';
    case CONSUMABLE = 'consumable';
    case OTHER = 'other';

    public function isControlled(): bool
    {
        return in_array($this, [
            self::MEDICATION,
            self::FEED,
            self::KITCHEN_PRODUCT,
            self::GROOMING_PRODUCT,
        ]);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MEDICATION => 'Лекарственные препараты',
            self::FEED => 'Корма и лакомства',
            self::GROOMING_PRODUCT => 'Косметика и средства для груминга',
            self::KITCHEN_PRODUCT => 'Продукты питания',
            self::CONSUMABLE => 'Расходные материалы',
            self::OTHER => 'Прочее',
        };
    }
}
