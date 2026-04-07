<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class StrApartment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'str_apartments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'property_id',
        'room_number',
        'floor',
        'area_sqm',
        'capacity_adults',
        'capacity_children',
        'base_price_b2c',
        'base_price_b2b',
        'deposit_amount',
        'min_stay_days',
        'is_available',
        'features_json',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'area_sqm' => 'integer',
        'capacity_adults' => 'integer',
        'capacity_children' => 'integer',
        'base_price_b2c' => 'integer',
        'base_price_b2b' => 'integer',
        'deposit_amount' => 'integer',
        'min_stay_days' => 'integer',
        'features_json' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(StrProperty::class, 'property_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(StrBooking::class, 'apartment_id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(StrAmenity::class, 'str_amenity_map', 'apartment_id', 'amenity_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(StrCalendarAvailability::class, 'apartment_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StrReview::class, 'apartment_id');
    }
}
