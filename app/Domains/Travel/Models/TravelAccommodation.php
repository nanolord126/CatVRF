<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelAccommodation extends Model
{

    use HasFactory;
        use SoftDeletes;

        protected $table = 'travel_accommodations';

        protected $fillable = [
            'tenant_id',
            'agency_id',
            'name',
            'description',
            'location',
            'geo_point',
            'type',
            'star_rating',
            'rooms_count',
            'price_per_night',
            'amenities',
            'photos',
            'rating',
            'review_count',
            'is_available',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'amenities' => 'collection',
            'photos' => 'collection',
            'price_per_night' => 'float',
            'rating' => 'float',
            'star_rating' => 'integer',
            'rooms_count' => 'integer',
            'review_count' => 'integer',
            'is_available' => 'boolean',
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
}
