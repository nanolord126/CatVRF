<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelAgency extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;
        use SoftDeletes;

        protected $table = 'travel_agencies';

        protected $fillable = [
            'tenant_id',
            'business_group_id',
            'owner_id',
            'name',
            'description',
            'address',
            'geo_point',
            'phone',
            'email',
            'specializations',
            'website',
            'logo_url',
            'rating',
            'review_count',
            'tour_count',
            'is_verified',
            'is_active',
            'license_number',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'specializations' => 'collection',
            'rating' => 'float',
            'review_count' => 'integer',
            'tour_count' => 'integer',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
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

        public function owner(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function tours(): HasMany
        {
            return $this->hasMany(TravelTour::class, 'agency_id');
        }

        public function bookings(): HasMany
        {
            return $this->hasMany(TravelBooking::class, 'agency_id');
        }

        public function guides(): HasMany
        {
            return $this->hasMany(TravelGuide::class, 'agency_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(TravelReview::class, 'agency_id');
        }
}
