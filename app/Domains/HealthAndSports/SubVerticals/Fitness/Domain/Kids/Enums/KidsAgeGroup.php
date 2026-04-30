<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Kids\Enums;

enum KidsAgeGroup: string
{
    case PRESCHOOL = 'preschool'; // 3-5 years
    case SCHOOL_6_8 = 'school_6_8'; // 6-8 years
    case SCHOOL_9_12 = 'school_9_12'; // 9-12 years
    case TEENS = 'teens'; // 13-17 years
}
