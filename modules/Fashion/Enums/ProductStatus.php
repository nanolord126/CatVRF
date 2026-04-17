<?php declare(strict_types=1);

namespace Modules\Fashion\Enums;

enum ProductStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case OUT_OF_STOCK = 'out_of_stock';
    case DISCONTINUED = 'discontinued';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::ACTIVE => 'Активен',
            self::OUT_OF_STOCK => 'Нет в наличии',
            self::DISCONTINUED => 'Снят с производства',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::ACTIVE => 'success',
            self::OUT_OF_STOCK => 'warning',
            self::DISCONTINUED => 'danger',
        };
    }
}
