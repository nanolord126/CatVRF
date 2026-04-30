<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Enums;

use App\Enums\BaseEnum;

/**
 * Customer Type — Тип клиента в CRM
 */
enum CustomerType: string implements BaseEnum
{
    case Individual = 'individual';
    case Business = 'business';
    case Vip = 'vip';
    case Wholesale = 'wholesale';
    case Corporate = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Физическое лицо',
            self::Business => 'Юридическое лицо',
            self::Vip => 'VIP клиент',
            self::Wholesale => 'Оптовик',
            self::Corporate => 'Корпоративный клиент',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Individual => 'blue',
            self::Business => 'purple',
            self::Vip => 'yellow',
            self::Wholesale => 'green',
            self::Corporate => 'indigo',
        };
    }
}
