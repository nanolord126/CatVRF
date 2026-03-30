<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RestaurantTable extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids;
        use SoftDeletes;

        protected $table = 'restaurant_tables';

        protected $fillable = [
            'tenant_id',
            'restaurant_id',
            'table_number',
            'seats',
            'status',
            'current_order_id',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => 'collection',
            'seats' => 'integer',
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
