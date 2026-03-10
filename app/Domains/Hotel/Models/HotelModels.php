<?php

namespace App\Domains\Hotel\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Contracts\Common\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Модель номера отеля.
 * 
 * @property int $id
 * @property int $hotel_id ID отеля
 * @property int $room_type_id ID типа номера
 * @property string $room_number Номер номера (101, 102, etc)
 * @property string $floor Этаж (1, 2, 3)
 * @property float $current_price Текущая цена за ночь
 * @property bool $is_active Активен ли номер
 * @property bool $is_dirty Требуется ли уборка
 * @property bool $is_blocked Блокирован ли номер (maintenance)
 * @property \Carbon\Carbon|null $last_cleaned_at Последняя уборка
 * @property array|null $amenities Удобства номера
 * @property array|null $metadata Метаданные
 */
class HotelRoom extends Model implements AIEnableEcosystemEntity
{
    use HasEcosystemFeatures;

    protected $table = 'hotel_rooms';

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'room_number',
        'floor',
        'current_price',
        'is_active',
        'is_dirty',
        'is_blocked',
        'last_cleaned_at',
        'amenities',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean',
        'is_dirty' => 'boolean',
        'is_blocked' => 'boolean',
        'last_cleaned_at' => 'datetime',
        'current_price' => 'float',
        'metadata' => 'array',
    ];

    /**
     * Получить динамическую цену на основе спроса и сезонности.
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        try {
            // Использовать переданную базовую цену или цену типа номера
            $base = $basePrice > 0 ? $basePrice : ($this->type?->base_price ?? 100);
            
            // Получить среднюю цену за последние 30 дней
            $avgHistoryPrice = DB::table('hotel_booking_history')
                ->where('room_id', $this->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('total_price') ?? $base;
            
            // Получить занятость за последние 7 дней
            $occupancyRate = DB::table('hotel_bookings')
                ->where('room_id', $this->id)
                ->where('check_in', '>=', now()->subDays(7))
                ->where('status', '!=', 'cancelled')
                ->count();
            
            $occupancyPercent = min(100, ($occupancyRate / 7) * 100);
            
            // Получить сезонный коэффициент
            $month = now()->month;
            $seasonalMultiplier = match (true) {
                in_array($month, [12, 1, 2]) => 1.25, // Зима - высокий сезон
                in_array($month, [6, 7, 8]) => 1.20,  // Лето - высокий сезон
                in_array($month, [3, 4, 5]) => 1.05,  // Весна - средний сезон
                in_array($month, [9, 10, 11]) => 0.95, // Осень - низкий сезон
                default => 1.0,
            };
            
            // Применить динамическую корректировку на основе спроса
            $demandMultiplier = match (true) {
                $occupancyPercent >= 80 => 1.30,  // Очень высокий спрос
                $occupancyPercent >= 70 => 1.15,  // Высокий спрос
                $occupancyPercent >= 50 => 1.05,  // Средний спрос
                $occupancyPercent >= 30 => 0.95,  // Низкий спрос
                default => 0.80,                   // Очень низкий спрос
            };
            
            $adjusted = $avgHistoryPrice * $seasonalMultiplier * $demandMultiplier;
            
            Log::channel('hotel')->debug('Room price adjusted', [
                'room_id' => $this->id,
                'base_price' => $base,
                'history_price' => $avgHistoryPrice,
                'occupancy' => $occupancyPercent,
                'seasonal_mul' => $seasonalMultiplier,
                'demand_mul' => $demandMultiplier,
                'final_price' => $adjusted,
                'context' => $context,
            ]);
            
            return round($adjusted, 2);
        } catch (\Exception $e) {
            Log::error('Error calculating adjusted price', ['error' => $e->getMessage()]);
            return $basePrice > 0 ? $basePrice : ($this->type?->base_price ?? 100);
        }
    }

    /**
     * Получить trust score номера (0-100).
     * На основе количества положительных отзывов и условия номера.
     */
    public function getTrustScore(): int
    {
        try {
            $score = 85; // Базовый score
            
            // Минус баллы если номер грязный
            if ($this->is_dirty) {
                $score -= 20;
            }
            
            // Минус баллы если заблокирован
            if ($this->is_blocked) {
                $score -= 15;
            }
            
            // Плюс баллы за давнюю уборку
            if ($this->last_cleaned_at?->diffInHours(now()) < 24) {
                $score += 5;
            }
            
            // Получить средний рейтинг от бронирований
            $avgRating = DB::table('hotel_booking_reviews')
                ->where('room_id', $this->id)
                ->avg('rating') ?? 5;
            
            $score += (int) ($avgRating * 3); // Максимум +15 баллов за 5 звезд
            
            return min(100, max(0, $score));
        } catch (\Exception $e) {
            Log::error('Error calculating trust score', ['error' => $e->getMessage()]);
            return 85;
        }
    }

    /**
     * Генерировать AI-checklist для уборки номера.
     */
    public function generateAiChecklist(): array
    {
        $checklist = [
            'Проверить кондиционер',
            'Наполнить мини-бар',
            'Дезинфицировать поверхности',
            'Проверить сантехнику',
            'Пополнить полотенца',
            'Очистить окна',
        ];
        
        // Добавить специфичные задачи на основе типа номера
        if ($this->type?->has_jacuzzi) {
            $checklist[] = 'Очистить джакузи';
        }
        
        if ($this->type?->has_kitchen) {
            $checklist[] = 'Вымыть кухню';
        }
        
        return $checklist;
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(HotelRoomType::class, 'room_type_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class, 'room_id');
    }

    public function housekeepingLogs(): HasMany
    {
        return $this->hasMany(HotelHousekeepingLog::class, 'room_id');
    }

    /**
     * Получить доступные номера на даты.
     */
    public function scopeAvailable(Builder $query, \DateTime $checkIn, \DateTime $checkOut): Builder
    {
        return $query->where('is_active', true)
            ->where('is_blocked', false)
            ->whereDoesntHave('bookings', function (Builder $q) use ($checkIn, $checkOut) {
                $q->whereBetween('check_in', [$checkIn, $checkOut])
                  ->orWhereBetween('check_out', [$checkIn, $checkOut]);
            });
    }

    /**
     * Номера, требующие уборки.
     */
    public function scopeDirty(Builder $query): Builder
    {
        return $query->where('is_dirty', true);
    }

    /**
     * Активные номера.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_blocked', false);
    }

    /**
     * Получить текущую занятость номера.
     */
    protected function occupancyStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bookings()
                ->where('check_in', '<=', now())
                ->where('check_out', '>=', now())
                ->where('status', '!=', 'cancelled')
                ->exists() ? 'occupied' : 'available',
        );
    }
}

/**
 * Модель типа номера отеля.
 * 
 * @property int $id
 * @property string $name Название типа (Single, Double, Suite)
 * @property int $capacity Вместимость гостей
 * @property float $base_price Базовая цена за ночь
 * @property array $amenities Удобства
 * @property bool $has_jacuzzi Есть ли джакузи
 * @property bool $has_kitchen Есть ли кухня
 * @property int $square_meters Площадь номера
 */
class HotelRoomType extends Model
{
    use HasEcosystemFeatures;

    protected $table = 'hotel_room_types';

    protected $fillable = [
        'hotel_id',
        'name',
        'capacity',
        'base_price',
        'amenities',
        'has_jacuzzi',
        'has_kitchen',
        'square_meters',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'amenities' => 'array',
        'base_price' => 'float',
        'has_jacuzzi' => 'boolean',
        'has_kitchen' => 'boolean',
        'square_meters' => 'integer',
        'capacity' => 'integer',
        'metadata' => 'array',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(HotelRoom::class, 'room_type_id');
    }

    /**
     * Получить общее количество номеров этого типа.
     */
    public function roomsCount(): int
    {
        return $this->rooms()->count();
    }

    /**
     * Получить количество доступных номеров.
     */
    public function availableCount(\DateTime $checkIn, \DateTime $checkOut): int
    {
        return $this->rooms()
            ->available($checkIn, $checkOut)
            ->count();
    }

    /**
     * Получить среднюю цену номеров этого типа за период.
     */
    public function averagePrice(\DateTime $from, \DateTime $to): float
    {
        return DB::table('hotel_booking_history')
            ->whereIn('room_id', $this->rooms()->pluck('id'))
            ->whereBetween('created_at', [$from, $to])
            ->avg('total_price') ?? $this->base_price;
    }

    /**
     * Получить процент занятости за период.
     */
    public function occupancyRate(\DateTime $from, \DateTime $to): float
    {
        $totalRoomDays = $this->rooms()->count() * now()->diffInDays($from);
        
        $bookedDays = DB::table('hotel_bookings')
            ->whereIn('room_id', $this->rooms()->pluck('id'))
            ->whereBetween('check_in', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->sum(DB::raw('DATEDIFF(check_out, check_in)'));
        
        return $totalRoomDays > 0 ? round(($bookedDays / $totalRoomDays) * 100, 2) : 0;
    }
}

/**
 * Модель бронирования номера в отеле.
 * 
 * @property int $id
 * @property int $room_id ID номера
 * @property int|null $user_id ID пользователя
 * @property string $guest_name Имя гостя
 * @property string $guest_email Email гостя
 * @property string $guest_phone Телефон гостя
 * @property \Carbon\Carbon $check_in Дата заезда
 * @property \Carbon\Carbon $check_out Дата выезда
 * @property float $total_price Общая стоимость
 * @property string $status Статус (pending, confirmed, checked_in, checked_out, cancelled)
 * @property bool $late_checkout Поздний checkout
 * @property array|null $metadata
 */
class HotelBooking extends Model
{
    use HasEcosystemFeatures;

    protected $table = 'hotel_bookings';

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_CHECKED_OUT = 'checked_out';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'room_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'check_in',
        'check_out',
        'total_price',
        'status',
        'late_checkout',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_price' => 'float',
        'late_checkout' => 'boolean',
        'metadata' => 'array',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HotelRoom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Получить количество ночей в бронировании.
     */
    public function nights(): int
    {
        return $this->check_in->diffInDays($this->check_out);
    }

    /**
     * Получить цену за ночь.
     */
    public function pricePerNight(): float
    {
        $nights = $this->nights();
        return $nights > 0 ? round($this->total_price / $nights, 2) : 0;
    }

    /**
     * Проверить, активно ли бронирование.
     */
    public function isActive(): bool
    {
        return $this->status !== self::STATUS_CANCELLED &&
            $this->status !== self::STATUS_CHECKED_OUT;
    }

    /**
     * Подтвердить бронирование.
     */
    public function confirm(): bool
    {
        try {
            $this->update(['status' => self::STATUS_CONFIRMED]);
            Log::channel('hotel')->info('Booking confirmed', ['booking_id' => $this->id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to confirm booking', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Зарегистрировать check-in.
     */
    public function checkIn(): bool
    {
        try {
            $this->update(['status' => self::STATUS_CHECKED_IN]);
            Log::channel('hotel')->info('Guest checked in', ['booking_id' => $this->id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to check in', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Зарегистрировать check-out.
     */
    public function checkOut(): bool
    {
        try {
            $this->update(['status' => self::STATUS_CHECKED_OUT]);
            Log::channel('hotel')->info('Guest checked out', ['booking_id' => $this->id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to check out', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Отменить бронирование.
     */
    public function cancel(string $reason = null): bool
    {
        try {
            $this->update([
                'status' => self::STATUS_CANCELLED,
                'metadata' => array_merge($this->metadata ?? [], ['cancellation_reason' => $reason]),
            ]);
            
            // Пометить номер как доступный
            if ($this->room) {
                $this->room->update(['is_dirty' => true]);
            }
            
            Log::channel('hotel')->info('Booking cancelled', [
                'booking_id' => $this->id,
                'reason' => $reason,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel booking', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Бронирования в статусе ожидания подтверждения.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Активные бронирования.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_CONFIRMED,
            self::STATUS_CHECKED_IN,
        ]);
    }

    /**
     * Получить дату и время выезда с учётом позднего checkout.
     */
    protected function actualCheckOut(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->late_checkout
                ? $this->check_out->addHours(4)
                : $this->check_out,
        );
    }
}

/**
 * Модель логов уборки номеров.
 * 
 * @property int $id
 * @property int $room_id ID номера
 * @property int $staff_id ID сотрудника уборки
 * @property \Carbon\Carbon $cleaned_at Время уборки
 * @property string $status Статус уборки (pending, in_progress, completed, failed)
 * @property string|null $notes Заметки о уборке
 * @property array|null $inspection_results Результаты проверки качества
 */
class HotelHousekeepingLog extends Model
{
    use HasEcosystemFeatures;

    protected $table = 'hotel_housekeeping_logs';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'room_id',
        'staff_id',
        'cleaned_at',
        'status',
        'notes',
        'inspection_results',
        'tenant_id',
    ];

    protected $casts = [
        'cleaned_at' => 'datetime',
        'inspection_results' => 'array',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HotelRoom::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'staff_id');
    }

    /**
     * Отметить уборку как выполненную.
     */
    public function markCompleted(array $inspectionResults = []): bool
    {
        try {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'cleaned_at' => now(),
                'inspection_results' => $inspectionResults,
            ]);
            
            // Обновить статус номера
            if ($this->room) {
                $this->room->update([
                    'is_dirty' => false,
                    'last_cleaned_at' => now(),
                ]);
            }
            
            Log::channel('hotel')->info('Housekeeping completed', [
                'log_id' => $this->id,
                'room_id' => $this->room_id,
                'staff_id' => $this->staff_id,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark housekeeping completed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Отметить уборку как неудачную.
     */
    public function markFailed(string $reason = null): bool
    {
        try {
            $this->update([
                'status' => self::STATUS_FAILED,
                'notes' => $reason,
            ]);
            
            Log::channel('hotel')->warning('Housekeeping failed', [
                'log_id' => $this->id,
                'room_id' => $this->room_id,
                'reason' => $reason,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark housekeeping failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Получить незавершённые уборки.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Получить успешно завершённые уборки.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}

