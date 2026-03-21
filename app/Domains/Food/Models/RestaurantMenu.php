<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $restaurant_id
 * @property string $name
 */
final class RestaurantMenu extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'restaurant_menus';

    protected $fillable = [
        'tenant_id',
        'restaurant_id',
        'name',
        'description',
        'sort_order',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
}
