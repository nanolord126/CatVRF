<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $address
 * @property float $rating
 * @property bool $is_verified
 */
final class Restaurant extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'restaurants';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'name',
        'description',
        'address',
        'geo_point',
        'cuisine_type',
        'schedule_json',
        'rating',
        'review_count',
        'phone',
        'website',
        'is_verified',
        'accepts_delivery',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'geo_point' => 'json',
        'cuisine_type' => 'collection',
        'schedule_json' => 'collection',
        'tags' => 'collection',
        'metadata' => 'json',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'accepts_delivery' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function menus(): HasMany
    {
        return $this->hasMany(RestaurantMenu::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(RestaurantOrder::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(FoodConsumable::class);
    }
}
