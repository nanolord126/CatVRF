<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Enums;

enum ContactType: string
{
    case Client = 'client';
    case Supplier = 'supplier';
    case Partner = 'partner';
    case DecisionMaker = 'decision_maker';
    case Influencer = 'influencer';
    case Technical = 'technical';
    case Financial = 'financial';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Клиент',
            self::Supplier => 'Поставщик',
            self::Partner => 'Партнер',
            self::DecisionMaker => 'ЛПР',
            self::Influencer => 'Влиятельное лицо',
            self::Technical => 'Технический контакт',
            self::Financial => 'Финансовый контакт',
        };
    }
}
