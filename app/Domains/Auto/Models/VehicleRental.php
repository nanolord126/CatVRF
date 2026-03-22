<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VehicleRental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_rentals';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'vehicle_id',
        'renter_id',
        'start_date',
        'end_date',
        'duration_days',
        'price_per_day',
        'total_price',
        'deposit_amount',
        'insurance_included',
        'status',
        'payment_status',
        'pickup_location',
        'dropoff_location',
        'notes',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'duration_days' => 'integer',
        'price_per_day' => 'integer',
        'total_price' => 'integer',
        'deposit_amount' => 'integer',
        'insurance_included' => 'boolean',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check() && tenancy()->initialized) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'renter_id');
    }
}
