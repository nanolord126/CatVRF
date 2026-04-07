<?php
declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use App\Models\User;

final class Ride extends Model
{
    protected $table = "taxi_rides";

    protected $fillable = [
        "tenant_id", "business_group_id", "uuid", "correlation_id",
        "driver_id", "customer_id", "pickup_lat", "pickup_lon", "pickup_address",
        "dropoff_lat", "dropoff_lon", "dropoff_address", "status", "price", "distance_km",
        "route_details", "metadata"
    ];

    protected $casts = [
        "route_details" => "json",
        "metadata" => "json",
        "price" => "decimal:2",
        "pickup_lat" => "decimal:8",
        "pickup_lon" => "decimal:8",
        "dropoff_lat" => "decimal:8",
        "dropoff_lon" => "decimal:8",
        "distance_km" => "decimal:2"
    ];

    protected static function booted(): void
    {
        static::addGlobalScope("tenant", function (Builder $query): void {
            if (app()->bound("tenant") && app("tenant") instanceof Tenant) {
                $query->where("tenant_id", app("tenant")->id);
            }
        });

        static::creating(function (Model $model): void {
            if (!$model->uuid) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!$model->correlation_id) {
                $model->correlation_id = request()->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

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
        return $this->belongsTo(User::class, "customer_id");
    }
}
