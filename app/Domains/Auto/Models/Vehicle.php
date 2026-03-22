<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicles';

    protected $fillable = [
        'tenant_id',
        'owner_id',
        'vin',
        'make',
        'model',
        'year',
        'license_plate',
        'color',
        'engine_type',
        'transmission',
        'mileage',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(AutoServiceOrder::class);
    }

    public function washBookings(): HasMany
    {
        return $this->hasMany(CarWashBooking::class);
    }

    public function detailings(): HasMany
    {
        return $this->hasMany(CarDetailing::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(VehicleInspection::class);
    }
}
