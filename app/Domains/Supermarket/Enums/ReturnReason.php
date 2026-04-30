<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Enums;

enum ReturnReason: string
{
    case SPOILED = 'spoiled';           // испорчено (самая частая)
    case WRONG_ITEM = 'wrong_item';     // не тот товар
    case CHANGED_MIND = 'changed_mind'; // передумал
    case DAMAGED = 'damaged';           // повреждено при доставке
    case EXPIRED = 'expired';           // просрочено
    case OTHER = 'other';               // другое
}
