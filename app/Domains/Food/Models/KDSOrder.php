<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $restaurant_order_id
 * @property string $status
 * @property int $total_cooking_time_minutes
 */
final class KDSOrder extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'kds_orders';

    protected $fillable = [
        'tenant_id',
        'restaurant_order_id',
        'items_json',
        'status',
        'total_cooking_time_minutes',
        'started_at',
        'ready_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'items_json' => 'collection',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'total_cooking_time_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id');
    }
}
