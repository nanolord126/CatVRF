<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\FreshProduce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Farm extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'produce_farms';
        protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'owner_id', 'address', 'phone', 'latitude', 'longitude', 'certification', 'is_verified', 'commission_percent', 'min_order', 'tags'];
        protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'latitude' => 'float', 'longitude' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function products() { return $this->hasMany(ProduceProduct::class, 'farm_id'); }
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function orders() { return $this->hasMany(ProduceOrder::class, 'farm_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('produce_farms.tenant_id', tenant()->id));
        }
}
