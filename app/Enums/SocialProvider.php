<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialProvider: string
{
    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Yandex => 'Яндекс',
            self::VK => 'ВКонтакте',
            self::Telegram => 'Telegram',
            self::Apple => 'Apple',
        };
    }

    public function getScopes(): array
    {
        return match ($this) {
            self::Google => ['openid', 'email', 'profile'],
            self::Yandex => ['login:email', 'login:info'],
            self::VK => ['email'],
            self::Telegram => [],
            self::Apple => ['email', 'name'],
        };
    }
    case Google = 'google';
    case Yandex = 'yandex';
    case VK = 'vk';
    case Telegram = 'telegram';
    case Apple = 'apple';
}
