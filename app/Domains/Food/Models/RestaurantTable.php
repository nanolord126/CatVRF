declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $restaurant_id
 * @property string $table_number
 * @property string $status
 */
final class RestaurantTable extends Model
{
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
