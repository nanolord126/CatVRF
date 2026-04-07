<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelTour extends Model
{

    use HasFactory;
        use SoftDeletes;

        protected $table = 'travel_tours';

        protected $fillable = [
            'tenant_id',
            'agency_id',
            'name',
            'description',
            'destination',
            'destination_point',
            'duration_days',
            'start_date',
            'end_date',
            'price',
            'cost_price',
            'max_participants',
            'current_participants',
            'itinerary',
            'inclusions',
            'tags',
            'status',
            'rating',
            'review_count',
            'is_active',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'itinerary' => 'collection',
            'inclusions' => 'collection',
            'tags' => 'collection',
            'price' => 'float',
            'cost_price' => 'float',
            'rating' => 'float',
            'max_participants' => 'integer',
            'current_participants' => 'integer',
            'duration_days' => 'integer',
            'review_count' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
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

        public function bookings(): HasMany
        {
            return $this->hasMany(TravelBooking::class, 'tour_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(TravelReview::class, 'tour_id');
        }
}
