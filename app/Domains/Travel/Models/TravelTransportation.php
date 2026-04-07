<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelTransportation extends Model
{

    use HasFactory;
        use SoftDeletes;

        protected $table = 'travel_transportation';

        protected $fillable = [
            'tenant_id',
            'agency_id',
            'type',
            'provider',
            'location_pickup',
            'location_dropoff',
            'pickup_time',
            'dropoff_time',
            'capacity',
            'available_count',
            'price',
            'commission_amount',
            'features',
            'status',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'features' => 'collection',
            'price' => 'float',
            'commission_amount' => 'float',
            'capacity' => 'integer',
            'available_count' => 'integer',
            'pickup_time' => 'datetime',
            'dropoff_time' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id'];

        public function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function agency(): BelongsTo
        {
            return $this->belongsTo(TravelAgency::class);
        }
}
