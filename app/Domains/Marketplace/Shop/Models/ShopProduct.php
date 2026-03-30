<?php declare(strict_types=1);

namespace App\Domains\Marketplace\Shop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShopProduct extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;
        use SoftDeletes;
        use BelongsToTenant;

        protected $table = 'shop_products';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'sku',
            'category',
            'price_kopeks',
            'compare_at_price_kopeks',
            'attributes',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'attributes' => 'json',
            'tags' => 'json',
        ];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function getFormattedPriceAttribute(): string
        {
            return number_format($this->price_kopeks / 100, 2, '.', ' ') . ' ₽';
        }
}
