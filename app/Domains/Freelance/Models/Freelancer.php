<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Freelancer extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'freelancers';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'full_name',
            'specialization',
            'bio',
            'hourly_rate_kopecks',
            'experience_years',
            'skills',
            'languages',
            'rating',
            'completed_orders_count',
            'status',
            'is_verified',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'skills' => 'json',
            'languages' => 'json',
            'tags' => 'json',
            'is_verified' => 'boolean',
            'hourly_rate_kopecks' => 'integer',
            'rating' => 'float',
            'experience_years' => 'integer',
            'completed_orders_count' => 'integer',
        ];

        /**
         * Авто-генерация UUID и привязка к тенанту.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder) {
                $builder->where('tenant_id', tenant()->id ?? 1);
            });
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function offers(): HasMany
        {
            return $this->hasMany(FreelanceServiceOffer::class, 'freelancer_id');
        }

        public function orders(): HasMany
        {
            return $this->hasMany(FreelanceOrder::class, 'freelancer_id');
        }

        public function portfolios(): HasMany
        {
            return $this->hasMany(FreelancePortfolio::class, 'freelancer_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(FreelanceReview::class, 'freelancer_id');
        }
}
