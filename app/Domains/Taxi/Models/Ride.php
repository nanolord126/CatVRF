<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

final class Ride extends Model
{
    use TenantScoped;

    protected $table = 'taxi_rides';

    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'driver_id', 'customer_id', 'pickup_lat', 'pickup_lon', 'pickup_address',
        'dropoff_lat', 'dropoff_lon', 'dropoff_address', 'status', 'price', 'distance_km',
        'route_details', 'metadata',
    ];

    protected $casts = [
        'route_details' => 'json',
        'metadata' => 'json',
        'price' => 'decimal:2',
        'pickup_lat' => 'decimal:8',
        'pickup_lon' => 'decimal:8',
        'dropoff_lat' => 'decimal:8',
        'dropoff_lon' => 'decimal:8',
        'distance_km' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
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
