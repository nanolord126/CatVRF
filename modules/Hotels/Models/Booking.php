<?php declare(strict_types=1);

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Booking extends Model
{
    use HasFactory, SoftDeletes;
    
        protected $table = 'hotel_bookings';
    
        protected $fillable = [
            'hotel_id',
            'room_id',
            'tenant_id',
            'user_id',
            'uuid',
            'check_in_at',
            'check_out_at',
            'nights_count',
            'price_per_night_kopeki',
            'total_price_kopeki',
            'status',
            'payment_id',
            'guest_name',
            'guest_email',
            'guest_phone',
            'guests_count',
            'correlation_id',
            'tags',
            'metadata',
        ];
    
        protected $casts = [
            'nights_count' => 'integer',
            'price_per_night_kopeki' => 'integer',
            'total_price_kopeki' => 'integer',
            'payment_id' => 'integer',
            'guests_count' => 'integer',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'tags' => 'json',
            'metadata' => 'json',
        ];
    
        protected $hidden = ['deleted_at'];
    
        /**
         * Статусы бронирования.
         */
        public const STATUS_PENDING = 'pending';
        public const STATUS_CONFIRMED = 'confirmed';
        public const STATUS_CHECKED_IN = 'checked_in';
        public const STATUS_CHECKED_OUT = 'checked_out';
        public const STATUS_CANCELLED = 'cancelled';
    
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
         * Получить номер, на который забронировано бронирование.
         */
        public function room(): BelongsTo
        {
            return $this->belongsTo(\Modules\Hotels\Models\Room::class);
        }
    
        /**
         * Получить отель.
         */
        public function hotel(): BelongsTo
        {
            return $this->belongsTo(\Modules\Hotels\Models\Hotel::class);
        }
    
        /**
         * Получить пользователя, сделавшего бронирование.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
    
        /**
         * Получить цену за ночь в рублях.
         */
        public function getPricePerNightInRubles(): float
        {
            return $this->price_per_night_kopeki / 100;
        }
    
        /**
         * Получить общую цену в рублях.
         */
        public function getTotalPriceInRubles(): float
        {
            return $this->total_price_kopeki / 100;
        }
    
        /**
         * Рассчитать комиссию платформы согласно КАНОН 2026.
         */
        public function calculateCommission(): int
        {
            $basePercent = 10; // 10% standard commission
            
            // Проверка условий для повышенной комиссии
            if (!tenant('inn') || tenant('commission_uplift')) {
                $basePercent += 20; // +20% Agency Premium
            }
    
            return (int) ($this->total_price_kopeki * ($basePercent / 100));
        }
    
        /**
         * Проверить, подтверждено ли бронирование.
         */
        public function isConfirmed(): bool
        {
            return $this->status === self::STATUS_CONFIRMED;
        }
    
        /**
         * Проверить, отменено ли бронирование.
         */
        public function isCancelled(): bool
        {
            return $this->status === self::STATUS_CANCELLED;
        }
    
        /**
         * Проверить, завершено ли бронирование.
         */
        public function isCompleted(): bool
        {
            return $this->status === self::STATUS_CHECKED_OUT;
        }
}
