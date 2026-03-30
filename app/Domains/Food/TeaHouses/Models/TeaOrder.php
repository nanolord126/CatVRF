<?php declare(strict_types=1);

namespace App\Domains\Food\TeaHouses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TeaOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'tea_orders';
        protected $fillable = ['uuid', 'tenant_id', 'house_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'items_json', 'ceremony_type', 'tags'];
        protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'items_json' => 'json', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function house() { return $this->belongsTo(TeaHouse::class, 'house_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('tea_orders.tenant_id', tenant()->id));
        }
}
