<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Enums;

enum ReturnReason: string
{
    case SPOILED = 'spoiled';
    case WRONG_ITEM = 'wrong_item';
    case CHANGED_MIND = 'changed_mind';
    case DAMAGED = 'damaged';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SPOILED => 'Испорчено',
            self::WRONG_ITEM => 'Неверный товар',
            self::CHANGED_MIND => 'Передумал',
            self::DAMAGED => 'Повреждено',
            self::OTHER => 'Другое',
        };
    }

    public function requiresPhoto(): bool
    {
        return match ($this) {
            self::SPOILED, self::DAMAGED => true,
            default => false,
        };
    }
}
