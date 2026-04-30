<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Converted = 'converted';
    case Won = 'won';
    case Lost = 'lost';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Contacted => 'Связались',
            self::Qualified => 'Квалифицирован',
            self::Proposal => 'Предложение',
            self::Negotiation => 'Переговоры',
            self::Converted => 'Конвертирован',
            self::Won => 'Выигран',
            self::Lost => 'Проигран',
            self::OnHold => 'На удержании',
        };
    }

    public function canConvert(): bool
    {
        return in_array($this, [self::Qualified, self::Proposal, self::Negotiation]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Won, self::Lost]);
    }
}
