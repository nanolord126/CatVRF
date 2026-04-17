<?php declare(strict_types=1);

namespace Modules\Fashion\Enums;

enum ReturnStatus: string
{
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case IN_TRANSIT = 'in_transit';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::REQUESTED => 'Запрошен',
            self::APPROVED => 'Одобрен',
            self::REJECTED => 'Отклонен',
            self::IN_TRANSIT => 'В пути',
            self::COMPLETED => 'Завершен',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::REQUESTED => 'warning',
            self::APPROVED => 'info',
            self::REJECTED => 'danger',
            self::IN_TRANSIT => 'primary',
            self::COMPLETED => 'success',
        };
    }
}
