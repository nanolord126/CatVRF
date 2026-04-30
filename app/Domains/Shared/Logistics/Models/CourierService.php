<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessGroup;
use App\Models\User;

final class CourierService extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'courier_services';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'user_id',
        'company_name',
        'description',
        'license_number',
        'vehicle_types',
        'service_radius',
        'base_rate',
        'per_km_rate',
        'rating',
        'delivery_count',
        'active_shipments',
        'is_verified',
        'is_active',
        'correlation_id',
    ];

    protected $casts = [
        'vehicle_types' => 'collection',
        'base_rate' => 'float',
        'per_km_rate' => 'float',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CourierRating::class);
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()?->id);
            }
        });
    }
}
