<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TravelFlight extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'travel_flights';

    protected $fillable = [
        'tenant_id',
        'agency_id',
        'airline',
        'flight_number',
        'departure_airport',
        'arrival_airport',
        'departure_time',
        'arrival_time',
        'duration_minutes',
        'class',
        'available_seats',
        'price',
        'commission_amount',
        'status',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'price' => 'float',
        'commission_amount' => 'float',
        'available_seats' => 'integer',
        'duration_minutes' => 'integer',
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class);
    }
}
