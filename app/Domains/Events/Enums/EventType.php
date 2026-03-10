<?php

namespace App\Domains\Events\Enums;

enum EventType: string
{
    case CONFERENCE = 'conference';
    case WORKSHOP = 'workshop';
    case SEMINAR = 'seminar';
    case MEETING = 'meeting';
    case CONCERT = 'concert';
    case FESTIVAL = 'festival';
    case EXHIBITION = 'exhibition';
    case SPORTS = 'sports';

    public function label(): string
    {
        return match($this) {
            self::CONFERENCE => 'Конференция',
            self::WORKSHOP => 'Мастер-класс',
            self::SEMINAR => 'Семинар',
            self::MEETING => 'Встреча',
            self::CONCERT => 'Концерт',
            self::FESTIVAL => 'Фестиваль',
            self::EXHIBITION => 'Выставка',
            self::SPORTS => 'Спортивное событие',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::CONFERENCE => 'users',
            self::WORKSHOP => 'hammer',
            self::SEMINAR => 'book',
            self::MEETING => 'handshake',
            self::CONCERT => 'music',
            self::FESTIVAL => 'star',
            self::EXHIBITION => 'image',
            self::SPORTS => 'trophy',
        };
    }
}
