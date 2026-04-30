<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Enums;

enum ToothStatus: string
{
    case HEALTHY = 'healthy';
    case CARIES = 'caries';
    case FILLED = 'filled';
    case CROWN = 'crown';
    case IMPLANT = 'implant';
    case EXTRACTED = 'extracted';
    case ROOT_CANAL = 'root_canal';
    case MOBILITY_1 = 'mobility_1';
    case MOBILITY_2 = 'mobility_2';
    case MOBILITY_3 = 'mobility_3';
    case MISSING = 'missing';
    case IMPACTED = 'impacted';
    case SUPERNUMERARY = 'supernumerary';

    public function getColor(): string
    {
        return match ($this) {
            self::HEALTHY => '#22c55e', // green
            self::CARIES => '#f97316', // orange
            self::FILLED => '#3b82f6', // blue
            self::CROWN => '#8b5cf6', // purple
            self::IMPLANT => '#1e3a8a', // dark blue
            self::EXTRACTED => '#ef4444', // red
            self::ROOT_CANAL => '#eab308', // yellow
            self::MOBILITY_1, self::MOBILITY_2, self::MOBILITY_3 => '#dc2626', // red gradient
            self::MISSING => '#6b7280', // gray
            self::IMPACTED => '#f59e0b', // amber
            self::SUPERNUMERARY => '#ec4899', // pink
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::HEALTHY => 'Здоровый',
            self::CARIES => 'Кариес',
            self::FILLED => 'Пломбированный',
            self::CROWN => 'Коронка',
            self::IMPLANT => 'Имплант',
            self::EXTRACTED => 'Удалённый',
            self::ROOT_CANAL => 'Лечёные каналы',
            self::MOBILITY_1 => 'Подвижность 1 степени',
            self::MOBILITY_2 => 'Подвижность 2 степени',
            self::MOBILITY_3 => 'Подвижность 3 степени',
            self::MISSING => 'Отсутствует',
            self::IMPACTED => 'Ретинированный',
            self::SUPERNUMERARY => 'Сверхкомплектный',
        };
    }

    public function requiresAttention(): bool
    {
        return in_array($this, [
            self::CARIES,
            self::ROOT_CANAL,
            self::MOBILITY_1,
            self::MOBILITY_2,
            self::MOBILITY_3,
            self::IMPACTED,
        ]);
    }
}
