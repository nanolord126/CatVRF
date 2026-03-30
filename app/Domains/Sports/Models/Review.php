<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Review extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'reviews';
        protected $fillable = [
            'tenant_id',
            'studio_id',
            'trainer_id',
            'reviewer_id',
            'booking_id',
            'rating',
            'title',
            'content',
            'categories',
            'verified_purchase',
            'published_at',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'categories' => AsCollection::class,
            'tags' => AsCollection::class,
            'verified_purchase' => 'boolean',
            'published_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                $query->where('tenant_id', tenant('id'));
            });
        }

        public function studio(): BelongsTo
        {
            return $this->belongsTo(Studio::class);
        }

        public function trainer(): BelongsTo
        {
            return $this->belongsTo(Trainer::class);
        }

        public function reviewer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'reviewer_id');
        }

        public function booking(): BelongsTo
        {
            return $this->belongsTo(Booking::class);
        }
}
