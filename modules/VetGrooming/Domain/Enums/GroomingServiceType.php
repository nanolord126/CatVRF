<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum GroomingServiceType: string
{
    case BATH = 'bath';
    case HAIRCUT = 'haircut';
    case TRIMMING = 'trimming';
    case NAIL_TRIMMING = 'nail_trimming';
    case EAR_CLEANING = 'ear_cleaning';
    case TEETH_CLEANING = 'teeth_cleaning';
    case SPA = 'spa';
    case FULL_GROOMING = 'full_grooming';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::BATH => 'Купание',
            self::HAIRCUT => 'Стрижка',
            self::TRIMMING => 'Тримминг',
            self::NAIL_TRIMMING => 'Стрижка когтей',
            self::EAR_CLEANING => 'Чистка ушей',
            self::TEETH_CLEANING => 'Чистка зубов',
            self::SPA => 'SPA-процедуры',
            self::FULL_GROOMING => 'Полный груминг',
            self::OTHER => 'Другое',
        };
    }

    public function getEstimatedDurationMinutes(): int
    {
        return match ($this) {
            self::BATH => 30,
            self::HAIRCUT => 60,
            self::TRIMMING => 45,
            self::NAIL_TRIMMING => 15,
            self::EAR_CLEANING => 15,
            self::TEETH_CLEANING => 20,
            self::SPA => 90,
            self::FULL_GROOMING => 120,
            self::OTHER => 45,
        };
    }
}
