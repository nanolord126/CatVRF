<?php
declare(strict_types=1);

namespace App\Domains\Hotels\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\User;

final class HotelBooking extends Model
{

    protected $table = "hotels_bookings";

    protected $fillable = [
        "tenant_id", "hotel_id", "room_id", "customer_id", "uuid", "correlation_id",
        "check_in", "check_out", "total_price", "status", "guests_count",
        "special_requests", "payment_status"
    ];

    protected $casts = [
        "check_in" => "date",
        "check_out" => "date",
        "total_price" => "decimal:2",
        "guests_count" => "integer",
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
                $model->correlation_id = app(\Illuminate\Http\Request::class)->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, "tenant_id");
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, "hotel_id");
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, "room_id");
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, "customer_id");
    }
}
