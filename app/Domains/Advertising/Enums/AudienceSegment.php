<?php

namespace App\Domains\Advertising\Enums;

enum AudienceSegment: string
{
    case NEW_USERS = 'new_users';
    case RETURNING_USERS = 'returning_users';
    case HIGH_VALUE = 'high_value';
    case AT_RISK = 'at_risk';
    case ENGAGED = 'engaged';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::NEW_USERS => 'Новые пользователи',
            self::RETURNING_USERS => 'Постоянные пользователи',
            self::HIGH_VALUE => 'Ценные клиенты',
            self::AT_RISK => 'Риск потери',
            self::ENGAGED => 'Активные пользователи',
            self::INACTIVE => 'Неактивные',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::NEW_USERS => 'Пользователи, впервые посетившие ваш сайт',
            self::RETURNING_USERS => 'Пользователи, которые ранее посещали ваш сайт',
            self::HIGH_VALUE => 'Пользователи с высокой стоимостью жизненного цикла',
            self::AT_RISK => 'Пользователи, которые могут перестать быть активными',
            self::ENGAGED => 'Активно взаимодействующие пользователи',
            self::INACTIVE => 'Пользователи, не активные в последнее время',
        };
    }
}
