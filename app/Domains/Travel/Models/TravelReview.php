<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelReview extends Model
{

    use HasFactory;
        use SoftDeletes;

        protected $table = 'travel_reviews';

        protected $fillable = [
            'tenant_id',
            'agency_id',
            'tour_id',
            'reviewer_id',
            'booking_id',
            'rating',
            'comment',
            'review_aspects',
            'verified_booking',
            'helpful_count',
            'unhelpful_count',
            'status',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'review_aspects' => 'collection',
            'rating' => 'integer',
            'helpful_count' => 'integer',
            'unhelpful_count' => 'integer',
            'verified_booking' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id'];

        protected static function booted(): void
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

        public function tour(): BelongsTo
        {
            return $this->belongsTo(TravelTour::class);
        }

        public function reviewer(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function booking(): BelongsTo
        {
            return $this->belongsTo(TravelBooking::class);
        }
}
