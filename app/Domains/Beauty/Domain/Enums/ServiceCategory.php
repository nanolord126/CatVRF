<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Beauty\Domain\Enums;

enum ServiceCategory: string
{
    case HAIRCUT = 'haircut';
    case MANICURE = 'manicure';
    case PEDICURE = 'pedicure';
    case COSMETOLOGY = 'cosmetology';
    case MASSAGE = 'massage';
    case STYLING = 'styling';
    case COLORING = 'coloring';

    public function label(): string
    {
        return match ($this) {
            self::HAIRCUT => 'Стрижка',
            self::MANICURE => 'Маникюр',
            self::PEDICURE => 'Педикюр',
            self::COSMETOLOGY => 'Косметология',
            self::MASSAGE => 'Массаж',
            self::STYLING => 'Укладка',
            self::COLORING => 'Окрашивание',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
