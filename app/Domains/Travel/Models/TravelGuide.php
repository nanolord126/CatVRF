<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelGuide extends Model
{

    use HasFactory;
        use SoftDeletes;

        protected $table = 'travel_guides';

        protected $fillable = [
            'tenant_id',
            'agency_id',
            'user_id',
            'full_name',
            'bio',
            'language',
            'specializations',
            'experience_years',
            'phone',
            'hourly_rate',
            'rating',
            'tour_count',
            'review_count',
            'is_available',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'specializations' => 'collection',
            'hourly_rate' => 'float',
            'rating' => 'float',
            'experience_years' => 'integer',
            'tour_count' => 'integer',
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

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }
}
