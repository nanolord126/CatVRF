<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Models;

use App\Models\BaseModel;
use Database\Factories\GroceryStoreFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель для управления продовольственными магазинами и супермаркетами
 * 
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $name
 * @property string $address
 * @property float $latitude
 * @property float $longitude
 * @property string|null $phone
 * @property array $schedule_json
 * @property float $rating
 * @property int $review_count
 * @property bool $is_verified
 * @property int|null $delivery_radius_km
 * @property float|null $commission_percent
 * @property string|null $api_provider (magnit, pyaterochka, vkusvelle и т.д.)
 * @property string|null $api_token
 * @property array $tags
 * @property string|null $correlation_id
 * @property \Illuminate\Support\Carbon|null $last_sync_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class GroceryStore extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'grocery_stores';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'phone',
        'schedule_json',
        'rating',
        'review_count',
        'is_verified',
        'delivery_radius_km',
        'commission_percent',
        'api_provider',
        'api_token',
        'tags',
        'correlation_id',
        'last_sync_at',
    ];

    protected $hidden = ['api_token'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rating' => 'float',
        'commission_percent' => 'float',
        'is_verified' => 'boolean',
        'schedule_json' => 'json',
        'tags' => 'json',
        'last_sync_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        if (function_exists('filament') && filament()->getTenant()) {
            static::addGlobalScope('business_group', function ($query) {
                $query->where('business_group_id', filament()->getTenant()->id);
            });
        }
    }

    protected static function newFactory(): GroceryStoreFactory
    {
        return GroceryStoreFactory::new();
    }

    // Relations
    public function products(): HasMany
    {
        return $this->hasMany(GroceryProduct::class, 'store_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(GroceryOrder::class, 'store_id');
    }

    public function deliverySlots(): HasMany
    {
        return $this->hasMany(DeliverySlot::class, 'store_id');
    }

    public function deliveryPartners(): HasMany
    {
        return $this->hasMany(DeliveryPartner::class, 'store_id');
    }

    // Accessors & Mutators
    public function getDistanceToPointKm(float $lat, float $lon): float
    {
        return $this->haversineDistance($this->latitude, $this->longitude, $lat, $lon);
    }

    public function isWithinDeliveryRadius(float $lat, float $lon): bool
    {
        return $this->getDistanceToPointKm($lat, $lon) <= ($this->delivery_radius_km ?? 10);
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
