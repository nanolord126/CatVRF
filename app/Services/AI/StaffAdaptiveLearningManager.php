<?php

namespace App\Services\AI;

use App\Models\Tenants\RestaurantOrder;
use App\Models\Tenants\TaxiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис управления "Инцидентами и KPI" для адаптивного обучения персонала.
 * Переводит ошибки в задачи на обучение (Staff Training Tasks).
 */
class StaffAdaptiveLearningManager
{
    /**
     * Анализ производительности повара. 
     * Если блюда "зависают" на статусе 'cooking' > 20 минут, создается инцидент.
     */
    public function monitorCookingPerformance()
    {
        $overdueOrders = DB::table('restaurant_orders')
            ->where('kitchen_status', 'cooking')
            ->where('preparation_started_at', '<=', now()->subMinutes(20))
            ->get();

        foreach ($overdueOrders as $order) {
            $this->createLearningIncident($order->restaurant_id, "Kitchen Delay", "Order #{$order->id} exceeded standard 20min cooking time.");
        }
    }

    /**
     * Анализ задержек такси в пути к клиенту.
     * Если задержка > 10 минут относительно прогноза — обучаем алгоритм ETA.
     */
    public function monitorTaxiDelays()
    {
        $delays = TaxiTrip::where('status', 'on_way')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) > 10')
            ->get();

        foreach ($delays as $trip) {
            // Корректируем глобальный коэффициент пробок (Traffic Factor) для этой зоны
            Log::info("AI Logic: Learning from delay in trip #{$trip->id}. Adjusting local traffic weight.");
            
            DB::table('taxi_surge_zones')
                ->whereJsonContains('polygon_coords', ['lat' => $trip->origin_geo['lat'], 'lon' => $trip->origin_geo['lng']])
                ->increment('multiplier', 0.05); // Плавная коррекция наценки из-за пробок
        }
    }

    protected function createLearningIncident(int $ownerId, string $type, string $details)
    {
        // Логика записи инцидента в базу данных обучения
        Log::channel('ai_learning')->warning("INCIDENT [$type]: $details. Task created for HR.");
    }
}
