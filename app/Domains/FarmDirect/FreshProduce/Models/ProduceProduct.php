<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\FreshProduce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProduceProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'produce_products';
        protected $fillable = ['uuid', 'tenant_id', 'farm_id', 'correlation_id', 'name', 'price_kopecks', 'unit', 'stock', 'seasonal', 'is_organic', 'tags'];
        protected $casts = ['price_kopecks' => 'integer', 'stock' => 'float', 'is_organic' => 'boolean', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('produce_products.tenant_id', tenant()->id));
        }
}
