<?php
declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\User;

final class FoodOrder extends Model
{
    protected $table = "food_orders";

    protected $fillable = [
        "tenant_id", "restaurant_id", "customer_id", "uuid", "correlation_id",
        "items", "total_price", "status", "delivery_address", 
        "delivery_lat", "delivery_lon", "courier_id", "estimated_delivery_time",
        "payment_status", "special_instructions"
    ];

    protected $casts = [
        "items" => "json",
        "total_price" => "decimal:2",
        "delivery_lat" => "decimal:8",
        "delivery_lon" => "decimal:8",
        "estimated_delivery_time" => "datetime",
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
        return $this->belongsTo(Tenant::class, "tenant_id");
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, "restaurant_id");
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, "customer_id");
    }
}
