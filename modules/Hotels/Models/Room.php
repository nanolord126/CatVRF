<?php declare(strict_types=1);

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Room extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;
    
        protected $table = 'hotel_rooms';
    
        protected $fillable = [
            'hotel_id',
            'tenant_id',
            'uuid',
            'number',
            'name',
            'price_kopeki',
            'status',
            'is_clean',
            'last_cleaned_at',
            'requires_housekeeping',
            'needs_laundry',
            'room_type',
            'square_meters',
            'capacity',
            'amenities',
            'photos',
            'star_rating',
            'correlation_id',
            'tags',
        ];
    
        protected $casts = [
            'price_kopeki' => 'integer',
            'capacity' => 'integer',
            'is_clean' => 'boolean',
            'requires_housekeeping' => 'boolean',
            'needs_laundry' => 'boolean',
            'square_meters' => 'float',
            'star_rating' => 'float',
            'amenities' => 'json',
            'photos' => 'json',
            'tags' => 'json',
            'last_cleaned_at' => 'datetime',
        ];
    
        protected $hidden = ['deleted_at'];
    
        /**
         * Статусы номера.
         */
        public const STATUS_AVAILABLE = 'available';
        public const STATUS_OCCUPIED = 'occupied';
        public const STATUS_MAINTENANCE = 'maintenance';
        public const STATUS_OUT_OF_SERVICE = 'out_of_service';
    
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
         * Получить отель, к которому относится номер.
         */
        public function hotel(): BelongsTo
        {
            return $this->belongsTo(\Modules\Hotels\Models\Hotel::class);
        }
    
        /**
         * Получить все бронирования номера.
         */
        public function bookings(): HasMany
        {
            return $this->hasMany(\Modules\Hotels\Models\Booking::class, 'room_id');
        }
    
        /**
         * Получить цену в рублях.
         */
        public function getPriceInRubles(): float
        {
            return $this->price_kopeki / 100;
        }
    
        /**
         * Установить цену в рублях.
         */
        public function setPriceInRubles(float $rubles): void
        {
            $this->price_kopeki = (int) ($rubles * 100);
        }
    
        /**
         * Проверить, свободен ли номер.
         */
        public function isAvailable(): bool
        {
            return $this->status === self::STATUS_AVAILABLE;
        }
    
        /**
         * Пометить номер как грязный и требующий уборки.
         */
        public function markAsDirty(): void
        {
            $this->update([
                'is_clean' => false,
                'requires_housekeeping' => true,
            ]);
        }
    
        /**
         * Отметить номер как чистый.
         */
        public function markAsClean(): void
        {
            $this->update([
                'is_clean' => true,
                'requires_housekeeping' => false,
                'last_cleaned_at' => now(),
            ]);
        }
}
