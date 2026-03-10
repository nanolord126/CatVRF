<?php

namespace App\Domains\Hotel\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Contracts\Common\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelRoom extends Model implements AIEnableEcosystemEntity
{
    use HasEcosystemFeatures;

    public function getAiAdjustedPrice(): float {
        // Получить динамическую цену на основе спроса и сезонности
        $basePrice = $this->type->base_price ?? 100;
        
        // Получить среднюю цену за последние 30 дней
        $avgHistoryPrice = \DB::table('hotel_booking_history')
            ->where('room_id', $this->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->avg('total_price') ?? $basePrice;
        
        // Получить занятость за последние 7 дней (0-100)
        $occupancyRate = \DB::table('hotel_bookings')
            ->where('room_id', $this->id)
            ->where('check_in', '>=', now()->subDays(7))
            ->count();
        $occupancyPercent = min(100, ($occupancyRate / 7) * 100);
        
        // Применить динамическую корректировку на основе спроса
        // Если занятость > 70%, повышаем цену на 15%
        // Если занятость < 30%, понижаем цену на 20%
        if ($occupancyPercent > 70) {
            $adjusted = $avgHistoryPrice * 1.15;
        } elseif ($occupancyPercent < 30) {
            $adjusted = $avgHistoryPrice * 0.80;
        } else {
            $adjusted = $avgHistoryPrice;
        }
        
        return round($adjusted, 2);
    }

    public function getTrustScore(): int { return 95; }

    public function generateAiChecklist(): array {
        return ['Check AC', 'Minibar refill', 'Sanitize surfaces'];
    }

    protected $guarded = [];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean',
        'is_dirty' => 'boolean',
        'is_blocked' => 'boolean',
        'last_cleaned_at' => 'datetime',
    ];

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
}

class HotelRoomType extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'amenities' => 'array',
        'base_price' => 'decimal:2',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(HotelRoom::class, 'room_type_id');
    }
}

class HotelBooking extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_price' => 'decimal:2',
        'late_checkout' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HotelRoom::class);
    }
}

class HotelHousekeepingLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cleaned_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HotelRoom::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'staff_id');
    }
}
