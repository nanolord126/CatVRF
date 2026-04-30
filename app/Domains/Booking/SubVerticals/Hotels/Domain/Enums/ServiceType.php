<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Enums;

enum ServiceType: string
{
    case BREAKFAST = 'breakfast';
    case TRANSFER = 'transfer';
    case SPA = 'spa';
    case MINIBAR = 'minibar';
    case LAUNDRY = 'laundry';
    case ROOM_SERVICE = 'room_service';
    case PARKING = 'parking';
    case EXCURSION = 'excursion';
    case ADDITIONAL_BED = 'additional_bed';
    case LATE_CHECKOUT = 'late_checkout';
    case EARLY_CHECKIN = 'early_checkin';

    public function getLabel(): string
    {
        return match ($this) {
            self::BREAKFAST => 'Завтрак',
            self::TRANSFER => 'Трансфер',
            self::SPA => 'SPA',
            self::MINIBAR => 'Мини-бар',
            self::LAUNDRY => 'Прачечная',
            self::ROOM_SERVICE => 'Room Service',
            self::PARKING => 'Парковка',
            self::EXCURSION => 'Экскурсия',
            self::ADDITIONAL_BED => 'Дополнительная кровать',
            self::LATE_CHECKOUT => 'Поздний выезд',
            self::EARLY_CHECKIN => 'Ранний заезд',
        };
    }
}
