<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Enums;

enum VerticalType: string
{
    case RESTAURANT = 'restaurant';
    case HOTEL = 'hotel';
    case BEAUTY = 'beauty';
    case AUTO = 'auto';
    case FASHION = 'fashion';
    case JEWELRY = 'jewelry';
    case BAKERY = 'bakery';
}
