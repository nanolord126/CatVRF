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
 * BeautySalon — Eloquent-модель салона красоты.
 *
 * Tenant-aware, business_group_id scoping.
 * Global scopes обеспечивают полную изоляцию данных.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $name
 * @property string $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $description
 * @property array|null $working_hours
 * @property array|null $geo_point
 * @property float $rating
 * @property int $review_count
 * @property bool $is_verified
 * @property array|null $tags
 * @property array|null $metadata
 * @property string|null $correlation_id
 */
final class BeautySalon extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'beauty_salons';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'address',
        'phone',
        'email',
        'description',
        'working_hours',
        'geo_point',
        'rating',
        'review_count',
        'is_verified',
        'tags',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'working_hours' => 'json',
        'geo_point' => 'json',
        'tags' => 'json',
        'metadata' => 'json',
        'is_verified' => 'boolean',
        'rating' => 'float',
        'review_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Инициализация модели — глобальные скоупы для изоляции данных.
     *
     * CANON 2026: tenant_id + business_group_id scoping обязательны.
     * correlation_id и uuid генерируются автоматически при creating.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoping', static function ($builder): void {
            if (function_exists('tenant') && tenant() !== null) {
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
     * Мастера, привязанные к салону.
     */
    public function masters(): HasMany
    {
        return $this->hasMany(Master::class, 'salon_id');
    }

    /**
     * Услуги, предоставляемые салоном.
     */
    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'salon_id');
    }

    /**
     * Записи (бронирования) салона.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'salon_id');
    }

    /**
     * Расходные материалы салона.
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(BeautyConsumable::class, 'salon_id');
    }

    /**
     * Продукты, продаваемые в салоне.
     */
    public function products(): HasMany
    {
        return $this->hasMany(BeautyProduct::class, 'salon_id');
    }

    /**
     * Отзывы о салоне.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'salon_id');
    }

    /**
     * Тенант, которому принадлежит салон.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Бизнес-группа (филиал / юрлицо).
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }
}
