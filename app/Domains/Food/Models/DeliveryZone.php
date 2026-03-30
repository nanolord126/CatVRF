<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryZone extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids;
        use SoftDeletes;

        protected $table = 'delivery_zones';

        protected $fillable = [
            'tenant_id',
            'restaurant_id',
            'name',
            'polygon',
            'base_delivery_price',
            'surge_multiplier',
            'max_delivery_time_minutes',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => 'collection',
            'polygon' => 'json',
            'surge_multiplier' => 'float',
            'base_delivery_price' => 'integer',
            'max_delivery_time_minutes' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
        }

        public function restaurant(): BelongsTo
        {
            return $this->belongsTo(Restaurant::class);
        }
}
