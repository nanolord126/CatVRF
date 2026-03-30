<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Trainer extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'trainers';
        protected $fillable = [
            'tenant_id',
            'studio_id',
            'user_id',
            'full_name',
            'bio',
            'specializations',
            'certifications',
            'experience_years',
            'avatar_url',
            'hourly_rate',
            'is_active',
            'is_verified',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'specializations' => AsCollection::class,
            'certifications' => AsCollection::class,
            'tags' => AsCollection::class,
            'rating' => 'float',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'hourly_rate' => 'float',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                $query->where('tenant_id', tenant('id'));
            });
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }

        public function studio(): BelongsTo
        {
            return $this->belongsTo(Studio::class);
        }

        public function classes(): HasMany
        {
            return $this->hasMany(ClassSession::class);
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(Review::class);
        }

        public function bookings(): HasMany
        {
            return $this->hasMany(Booking::class);
        }
}
