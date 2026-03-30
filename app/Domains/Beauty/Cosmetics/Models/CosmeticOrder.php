<?php declare(strict_types=1);

namespace App\Domains\Beauty\Cosmetics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CosmeticOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes, TenantScoped;

        protected $table = 'cosmetic_orders';
        protected $fillable = [
            'tenant_id', 'uuid', 'correlation_id',
            'product_id', 'user_id', 'quantity', 'total_price', 'status', 'meta'
        ];
        protected $casts = [
            'quantity' => 'int',
            'total_price' => 'int',
            'meta' => 'json',
        ];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function product()
        {
            return $this->belongsTo(CosmeticProduct::class);
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }
}
