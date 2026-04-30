<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Enums;

use App\Enums\BaseEnum;

/**
 * Interaction Type — Тип взаимодействия с клиентом
 */
enum InteractionType: string implements BaseEnum
{
    case Call = 'call';
    case Email = 'email';
    case Sms = 'sms';
    case Visit = 'visit';
    case Order = 'order';
    case Support = 'support';
    case Complaint = 'complaint';
    case Feedback = 'feedback';
    case Referral = 'referral';
    case Note = 'note';
    case Task = 'task';
    case Meeting = 'meeting';
    case Chat = 'chat';
    case Social = 'social';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Звонок',
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Visit => 'Визит',
            self::Order => 'Заказ',
            self::Support => 'Поддержка',
            self::Complaint => 'Жалоба',
            self::Feedback => 'Отзыв',
            self::Referral => 'Реферал',
            self::Note => 'Заметка',
            self::Task => 'Задача',
            self::Meeting => 'Встреча',
            self::Chat => 'Чат',
            self::Social => 'Соцсеть',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => 'heroicon-o-phone',
            self::Email => 'heroicon-o-envelope',
            self::Sms => 'heroicon-o-chat-bubble-left-right',
            self::Visit => 'heroicon-o-building-storefront',
            self::Order => 'heroicon-o-shopping-cart',
            self::Support => 'heroicon-o-lifebuoy',
            self::Complaint => 'heroicon-o-exclamation-triangle',
            self::Feedback => 'heroicon-o-star',
            self::Referral => 'heroicon-o-user-plus',
            self::Note => 'heroicon-o-document-text',
            self::Task => 'heroicon-o-clipboard',
            self::Meeting => 'heroicon-o-users',
            self::Chat => 'heroicon-o-chat-bubble-left',
            self::Social => 'heroicon-o-share',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Call => 'blue',
            self::Email => 'purple',
            self::Sms => 'green',
            self::Visit => 'orange',
            self::Order => 'indigo',
            self::Support => 'cyan',
            self::Complaint => 'red',
            self::Feedback => 'yellow',
            self::Referral => 'pink',
            self::Note => 'gray',
            self::Task => 'slate',
            self::Meeting => 'teal',
            self::Chat => 'emerald',
            self::Social => 'violet',
        };
    }
}
