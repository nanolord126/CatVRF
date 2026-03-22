<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ParkingLot extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parking_lots';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'name',
        'address',
        'lat',
        'lng',
        'total_spots',
        'available_spots',
        'price_per_hour',
        'is_covered',
        'is_secured',
        'operating_hours',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'total_spots' => 'integer',
        'available_spots' => 'integer',
        'price_per_hour' => 'integer',
        'is_covered' => 'boolean',
        'is_secured' => 'boolean',
        'operating_hours' => 'json',
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

    public function bookings(): HasMany
    {
        return $this->hasMany(ParkingBooking::class);
    }
}
