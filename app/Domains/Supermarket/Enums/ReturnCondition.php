<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Enums;

enum ReturnCondition: string
{
    case GOOD = 'good';       // в хорошем состоянии
    case SPOILED = 'spoiled'; // испорчено
    case DAMAGED = 'damaged'; // повреждено
    case OPENED = 'opened';   // вскрыто
}
