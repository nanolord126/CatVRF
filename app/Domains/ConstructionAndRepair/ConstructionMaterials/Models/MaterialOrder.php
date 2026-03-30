<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\ConstructionMaterials\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MaterialOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes, TenantScoped;

        protected $table = 'material_orders';
        protected $fillable = [
            'tenant_id', 'uuid', 'correlation_id',
            'material_id', 'user_id', 'quantity', 'total_price',
            'status', 'delivery_address', 'tracking_number', 'meta'
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
        public function material()
        {
            return $this->belongsTo(ConstructionMaterial::class);
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
