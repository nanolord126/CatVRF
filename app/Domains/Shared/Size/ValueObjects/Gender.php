<?php

declare(strict_types=1);

namespace App\Domains\Shared\Size\ValueObjects;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case UNISEX = 'unisex';
    case KIDS = 'kids';
    case BABY = 'baby';

    public function getLabel(): string
    {
        return match ($this) {
            self::MALE => 'Мужской',
            self::FEMALE => 'Женский',
            self::UNISEX => 'Унисекс',
            self::KIDS => 'Детский',
            self::BABY => 'Для младенцев',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::MALE => 'heroicon-o-user',
            self::FEMALE => 'heroicon-o-user',
            self::UNISEX => 'heroicon-o-users',
            self::KIDS => 'heroicon-o-academic-cap',
            self::BABY => 'heroicon-o-baby',
        };
    }
}
