<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Master — Eloquent-модель мастера салона красоты.
 *
 * Tenant-aware с глобальным скоупом.
 * Привязан к салону, имеет услуги, записи, портфолио, отзывы.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $salon_id
 * @property int|null $user_id
 * @property string $full_name
 * @property array|null $specialization
 * @property int $experience_years
 * @property float $rating
 * @property int $review_count
 * @property string|null $bio
 * @property array|null $tags
 * @property string|null $correlation_id
 */
final class Master extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

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

    /**
     * CANON 2026: tenant scoping + auto uuid/correlation_id.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoping', static function ($builder): void {
            if (function_exists('tenant') && tenant()->id) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Салон, к которому привязан мастер.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    /**
     * Пользователь-аккаунт мастера.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Услуги, которые предоставляет мастер.
     */
    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'master_id');
    }

    /**
     * Записи к мастеру.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'master_id');
    }

    /**
     * Портфолио работ мастера.
     */
    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'master_id');
    }

    /**
     * Отзывы о мастере.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'master_id');
    }

    /**
     * Расписание мастера.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(MasterSchedule::class, 'master_id');
    }
}
