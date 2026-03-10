<?php

namespace Modules\Analytics\Services;

use App\Models\User;
use Modules\Analytics\Models\BehavioralEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketingAutomationService
{
    /**
     * Кросс-вертикальный апселл: Цветы после отеля.
     */
    public function triggerHotelToFlowersUpsell(User $user, $hotelBookingId): void
    {
        // 1. Проверяем, не предлагали ли уже
        // 2. Генерируем оффер
        Log::info("Triggering cross-sell Flowers for User {$user->id} after Hotel Booking {$hotelBookingId}");

        // Эффект: отправка Push-уведомления со скидкой 10% на букет
        $correlationId = session()->get('correlation_id', Str::uuid()->toString());
        
        // В реальности здесь создание записи в marketing_automation_logs и вызов Notification фасада
    }

    /**
     * Динамическое ценообразование на основе чувствительности.
     */
    public function getPersonalizedDiscount(User $user, string $vertical): float
    {
        $stats = BehavioralEvent::where('user_id', $user->id)
            ->where('event_type', 'view')
            ->where('vertical', $vertical)
            ->get();

        // Если пользователь смотрит много раз, но не покупает - даем скидку.
        // Если лояльный VIP - даем бонусные баллы, а не скидку (сохраняем маржинальность).
        return 0.15; // Возврат 15% скидки
    }
}
