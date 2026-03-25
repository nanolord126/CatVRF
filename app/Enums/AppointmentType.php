<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * AppointmentType (Canon 2026)
 * Единый перечень типов событий для всей платформы.
 */
enum AppointmentType: string
{
    case STANDARD = 'standard';
    case GROUP = 'group';
    case WEDDING = 'wedding';
    case KIDS_PARTY = 'kids_party';
    case CORPORATE = 'corporate';
    case PHOTO_SESSION = 'photo_session';
    case MASTER_CLASS = 'master_class';
    case OUTDOOR = 'outdoor'; // Выездные услуги
    case GIFT_CERTIFICATE = 'gift_certificate';
    case SUBSCRIPTION = 'subscription';
    case LUXURY = 'luxury';
    case AI_CONSTRUCTED = 'ai_constructed';
}
