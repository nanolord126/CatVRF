<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Room Model (Layer 2)
 * 
 * Номер в отеле (категория и сток).
 */
final class Room extends Model
{
    protected $table = 'hotel_rooms';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'hotel_id',
        'room_number',
        'room_type',
        'capacity_adults',
        'capacity_children',
        'base_price_b2c',
        'base_price_b2b',
        'total_stock',
        'min_stay_days',
        'is_available',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'metadata' => 'json',
        'base_price_b2c' => 'integer',
        'base_price_b2b' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (int) tenant('id');
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (int) tenant('id'));
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Получить цену для конкретного режима (B2C/B2B)
     */
    public function getBasePrice(string $mode = 'b2c'): int
    {
        return $mode === 'b2b' ? $this->base_price_b2b : $this->base_price_b2c;
    }
}
