<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use Illuminate\Support\Str;

final class Restaurant extends Model
{
    use TenantScoped;

    protected $table = 'food_restaurants';

    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'name', 'description', 'address', 'phone', 'email',
        'lat', 'lon', 'is_active', 'rating', 'delivery_radius_km',
        'working_hours', 'metadata',
    ];

    protected $casts = [
        'working_hours' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'lat' => 'decimal:8',
        'lon' => 'decimal:8',
        'rating' => 'decimal:2',
        'delivery_radius_km' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class, 'restaurant_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FoodOrder::class, 'restaurant_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant') && app('tenant') instanceof Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });

        self::creating(function (Model $model): void {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
            if (! $model->correlation_id) {
                $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
            }
        });
    }
}
