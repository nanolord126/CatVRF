<?php declare(strict_types=1);

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Hotel extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;
    
        protected $table = 'hotels';
    
        protected $fillable = [
            'tenant_id',
            'business_group_id',
            'uuid',
            'name',
            'stars',
            'category',
            'address',
            'latitude',
            'longitude',
            'room_count',
            'phone_number',
            'email',
            'website',
            'manager_id',
            'registration_number',
            'status',
            'correlation_id',
            'tags',
            'amenities',
            'metadata',
            'average_rating',
            'review_count',
        ];
    
        protected $casts = [
            'stars' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'room_count' => 'integer',
            'manager_id' => 'integer',
            'average_rating' => 'float',
            'review_count' => 'integer',
            'tags' => 'json',
            'amenities' => 'json',
            'metadata' => 'json',
        ];
    
        protected $hidden = ['deleted_at'];
    
        /**
         * Статусы отеля.
         */
        public const string STATUS_ACTIVE = 'active';
        public const string STATUS_INACTIVE = 'inactive';
        public const string STATUS_MAINTENANCE = 'maintenance';
    
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
         * Получить все номера отеля.
         */
        public function rooms(): HasMany
        {
            return $this->hasMany(\Modules\Hotels\Models\Room::class, 'hotel_id');
        }
    
        /**
         * Получить все бронирования отеля.
         */
        public function bookings(): HasMany
        {
            return $this->hasMany(\Modules\Hotels\Models\Booking::class, 'hotel_id');
        }
    
        /**
         * Получить управляющего отеля.
         */
        public function manager(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'manager_id');
        }
    
        /**
         * Получить среднюю оценку отеля.
         */
        public function getAverageRating(): float
        {
            return $this->average_rating ?? 0.0;
        }
    
        /**
         * Увеличить счетчик отзывов.
         */
        public function incrementReviewCount(): void
        {
            $this->increment('review_count');
        }
    
        /**
         * Получить количество свободных номеров на дату.
         */
        public function getAvailableRoomsCount(\Carbon\Carbon $date): int
        {
            return $this->rooms()
                ->whereDoesntHave('bookings', function ($query) use ($date) {
                    $query->whereDate('check_in_at', '<=', $date)
                        ->whereDate('check_out_at', '>=', $date)
                        ->where('status', '!=', 'cancelled');
                })
                ->count();
        }
    
        /**
         * Получить статус активности.
         */
        public function isActive(): bool
        {
            return $this->status === self::STATUS_ACTIVE;
        }
}
