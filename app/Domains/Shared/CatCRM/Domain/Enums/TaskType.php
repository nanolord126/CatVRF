<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Enums;

use App\Enums\BaseEnum;

/**
 * Task Type — Тип задачи в CRM
 */
enum TaskType: string implements BaseEnum
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case FollowUp = 'follow_up';
    case Document = 'document';
    case Payment = 'payment';
    case Delivery = 'delivery';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Звонок',
            self::Email => 'Email',
            self::Meeting => 'Встреча',
            self::FollowUp => 'Дозвон/Напоминание',
            self::Document => 'Документ',
            self::Payment => 'Оплата',
            self::Delivery => 'Доставка',
            self::Custom => 'Другое',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => 'heroicon-o-phone',
            self::Email => 'heroicon-o-envelope',
            self::Meeting => 'heroicon-o-users',
            self::FollowUp => 'heroicon-o-bell',
            self::Document => 'heroicon-o-document',
            self::Payment => 'heroicon-o-banknotes',
            self::Delivery => 'heroicon-o-truck',
            self::Custom => 'heroicon-o-plus',
        };
    }
}
