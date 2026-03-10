<?php

namespace App\Domains\Food\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use App\Contracts\Common\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestaurantOrder extends Model implements AIEnableEcosystemEntity
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'is_tax_inclusive' => 'boolean',
    ];

    /**
     * Получить динамически скорректированную цену от AI движка.
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        try {
            // Получить историческую среднюю цену заказов за последние 30 дней
            $avgHistoryPrice = DB::table('restaurant_orders')
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('total_amount') ?? $basePrice;
            
            // Скорректировать по времени суток (ужин дороже, чем завтрак)
            $hour = now()->hour;
            $timeMultiplier = match (true) {
                $hour >= 19 && $hour <= 23 => 1.15,  // Ужин: +15%
                $hour >= 12 && $hour <= 14 => 1.10,  // Обед: +10%
                default => 1.0,
            };
            
            // Скорректировать по спросу (выходные дороже)
            $dayOfWeek = now()->dayOfWeek;
            $dayMultiplier = in_array($dayOfWeek, [0, 6]) ? 1.08 : 1.0; // Выходные: +8%
            
            $adjustedPrice = $avgHistoryPrice * $timeMultiplier * $dayMultiplier;
            
            Log::channel('food')->info('RestaurantOrder price adjusted', [
                'base_price' => $basePrice,
                'adjusted_price' => $adjustedPrice,
                'time_multiplier' => $timeMultiplier,
                'day_multiplier' => $dayMultiplier,
            ]);
            
            return (float) $adjustedPrice;
        } catch (\Exception $e) {
            Log::error('Failed to calculate adjusted price', ['error' => $e->getMessage()]);
            return $basePrice;
        }
    }

    /**
     * Получить оценку надежности заказа (0-100).
     */
    public function getTrustScore(): int
    {
        // Базовая оценка для ресторана: 85 (высокая надежность)
        return 85;
    }

    /**
     * Сгенерировать AI чек-лист для выполнения заказа.
     */
    public function generateAiChecklist(): array
    {
        return [
            'Все блюда подготовлены',
            'Правильный номер стола',
            'Качество приготовления проверено',
            'Напитки подано',
            'Готовность к подаче',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantOrderItem::class, 'order_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'waiter_id');
    }
}

class RestaurantOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'notes' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(RestaurantMenuItem::class);
    }
}

class RestaurantTable extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(RestaurantOrder::class, 'table_id');
    }
}

class RestaurantMenuItem extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(RestaurantCategory::class);
    }
}

class RestaurantCategory extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantMenuItem::class, 'category_id');
    }
}
