<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Enums;

enum ReturnStatus: string
{
    case PENDING = 'pending';       // ожидает рассмотрения
    case APPROVED = 'approved';     // одобрен
    case REJECTED = 'rejected';     // отклонен
    case COMPLETED = 'completed';   // завершен
    case REFUNDED = 'refunded';     // деньги возвращены
}
