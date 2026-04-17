<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DeliveryPartner extends Model
{

    protected $table = 'delivery_partners';

    protected $fillable = [
        'uuid', 'tenant_id', 'store_id', 'user_id', 'status', 'vehicle_type',
        'phone', 'rating', 'completed_orders', 'current_location_lat', 'current_location_lon',
        'working_hours_json', 'tags', 'correlation_id'
    ];

    protected $casts = [
        'rating' => 'float',
        'completed_orders' => 'integer',
        'current_location_lat' => 'float',
        'current_location_lon' => 'float',
        'working_hours_json' => 'json',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(GroceryStore::class, 'store_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(GroceryOrder::class, 'delivery_partner_id');
    }
}
