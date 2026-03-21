<?php

declare(strict_types=1);

namespace Modules\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель услуги салона красоты.
 * Согласно КАНОН 2026: услуги (стрижка, маникюр, массаж и т.д.) с ценами, расходниками, временем.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $salon_id
 * @property string|null $uuid
 * @property string $name Название услуги
 * @property string|null $description Описание
 * @property int $price_kopeki Цена в копейках
 * @property int $duration_minutes Длительность в минутах
 * @property bool $is_active Активна ли услуга
 * @property string|null $category Категория
 * @property float|null $rating Рейтинг услуги
 * @property int|null $review_count Количество отзывов
 * @property array|null $consumables_json Расходники (JSON)
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beauty_services';

    protected $fillable = [
        'salon_id',
        'tenant_id',
        'uuid',
        'name',
        'description',
        'price_kopeki',
        'duration_minutes',
        'is_active',
        'category',
        'rating',
        'review_count',
        'consumables_json',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'price_kopeki' => 'integer',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'rating' => 'float',
        'review_count' => 'integer',
        'consumables_json' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Категории услуг.
     */
    public const CATEGORY_HAIR = 'hair';
    public const CATEGORY_NAILS = 'nails';
    public const CATEGORY_MASSAGE = 'massage';
    public const CATEGORY_SKIN_CARE = 'skin_care';
    public const CATEGORY_COSMETIC = 'cosmetic';

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить салон.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    /**
     * Получить бронирования.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'service_id');
    }

    /**
     * Получить цену в рублях.
     */
    public function getPriceInRubles(): float
    {
        return (float) ($this->price_kopeki / 100);
    }

    /**
     * Установить цену в рублях.
     */
    public function setPriceInRubles(float $rubles): void
    {
        $this->price_kopeki = (int) ($rubles * 100);
    }

    /**
     * Получить расходники.
     */
    public function getConsumables(): array
    {
        return $this->consumables_json ?? [];
    }

    /**
     * Проверить, активна ли услуга.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Получить среднюю оценку.
     */
    public function getAverageRating(): float
    {
        return (float) $this->rating;
    }

    /**
     * Увеличить количество отзывов.
     */
    public function incrementReviewCount(): void
    {
        $this->increment('review_count');
    }

    /**
     * Scope для активных услуг.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для конкретного салона.
     */
    public function scopeForSalon($query, int $salonId)
    {
        return $query->where('salon_id', $salonId);
    }
}
