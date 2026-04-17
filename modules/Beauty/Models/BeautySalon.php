<?php declare(strict_types=1);

namespace Modules\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BeautySalon extends Model
{
    use HasFactory, SoftDeletes;
    
        protected $table = 'beauty_salons';
    
        protected $fillable = [
            'tenant_id',
            'uuid',
            'name',
            'description',
            'phone',
            'email',
            'address',
            'latitude',
            'longitude',
            'category',
            'rating',
            'review_count',
            'is_verified',
            'is_active',
            'schedule_json',
            'commission_percent',
            'correlation_id',
            'metadata',
        ];
    
        protected $casts = [
            'latitude' => 'float',
            'longitude' => 'float',
            'rating' => 'float',
            'review_count' => 'integer',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'commission_percent' => 'integer',
            'schedule_json' => 'json',
            'metadata' => 'json',
        ];
    
        protected $hidden = ['deleted_at'];
    
        /**
         * Категории салонов.
         */
        public const CATEGORY_HAIR = 'hair';
        public const CATEGORY_NAILS = 'nails';
        public const CATEGORY_MASSAGE = 'massage';
        public const CATEGORY_SKIN_CARE = 'skin_care';
        public const CATEGORY_ALL = 'all';
    
        /**
         * Комиссия платформы по умолчанию (в процентах).
         */
        public const DEFAULT_COMMISSION_PERCENT = 14;
    
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
         * Получить услуги салона.
         */
        public function services(): HasMany
        {
            return $this->hasMany(Service::class, 'salon_id');
        }
    
        /**
         * Получить бронирования.
         */
        public function bookings(): HasMany
        {
            return $this->hasMany(Booking::class, 'salon_id');
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
         * Получить количество активных услуг.
         */
        public function getActiveServicesCount(): int
        {
            return $this->services()->where('is_active', true)->count();
        }
    
        /**
         * Проверить, активен ли салон.
         */
        public function isActive(): bool
        {
            return (bool) $this->is_active;
        }
    
        /**
         * Проверить, проверен ли салон.
         */
        public function isVerified(): bool
        {
            return (bool) $this->is_verified;
        }
}
