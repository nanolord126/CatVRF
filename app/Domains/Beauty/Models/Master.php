<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Master extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $table = 'masters';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'salon_id',
            'user_id',
            'full_name',
            'specialization',
            'experience_years',
            'rating',
            'review_count',
            'bio',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'specialization' => 'json',
            'tags' => 'json',
            'experience_years' => 'integer',
            'rating' => 'float',
            'review_count' => 'integer',
            'deleted_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scoping', function ($builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', tenant('id'));
                }
            });
        }

        /**
         * Отношения
         */
        public function salon(): BelongsTo
        {
            return $this->belongsTo(BeautySalon::class, 'salon_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        public function services(): HasMany
        {
            return $this->hasMany(BeautyService::class, 'master_id');
        }

        public function appointments(): HasMany
        {
            return $this->hasMany(Appointment::class, 'master_id');
        }

        public function portfolioItems(): HasMany
        {
            return $this->hasMany(PortfolioItem::class, 'master_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(Review::class, 'master_id');
        }

        public function schedules(): HasMany
        {
            return $this->hasMany(MasterSchedule::class, 'master_id');
        }
}
