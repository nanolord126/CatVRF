<?php

namespace App\Domains\Advertising\Enums;

enum CampaignType: string
{
    case DISPLAY = 'display';
    case SEARCH = 'search';
    case SOCIAL = 'social';
    case VIDEO = 'video';
    case EMAIL = 'email';
    case INFLUENCER = 'influencer';

    public function label(): string
    {
        return match($this) {
            self::DISPLAY => 'Баннерная реклама',
            self::SEARCH => 'Поиск',
            self::SOCIAL => 'Социальные сети',
            self::VIDEO => 'Видео',
            self::EMAIL => 'Email маркетинг',
            self::INFLUENCER => 'Инфлюэнсер маркетинг',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::DISPLAY => 'square',
            self::SEARCH => 'search',
            self::SOCIAL => 'share-2',
            self::VIDEO => 'play-circle',
            self::EMAIL => 'mail',
            self::INFLUENCER => 'star',
        };
    }
}
