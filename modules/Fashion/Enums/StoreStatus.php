<?php declare(strict_types=1);

namespace Modules\Fashion\Enums;

enum StoreStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'На модерации',
            self::ACTIVE => 'Активен',
            self::SUSPENDED => 'Приостановлен',
            self::CLOSED => 'Закрыт',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'danger',
            self::CLOSED => 'secondary',
        };
    }
}
