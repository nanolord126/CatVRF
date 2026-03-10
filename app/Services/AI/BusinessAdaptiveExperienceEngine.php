<?php

namespace App\Services\AI;

use App\Models\Tenants\RestaurantOrder;
use App\Models\Tenants\TaxiTrip;
use App\Models\Tenants\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис адаптивного обучения на ошибках и задержках (2026 Canon Engine).
 * Автоматически корректирует параметры системы (цены, тайминги, допуски)
 * на основе анализа инцидентов и отклонений от нормы.
 */
class BusinessAdaptiveExperienceEngine
{
    /**
     * Анализ и коррекция на основе задержек кухни.
     * Если среднее время приготовления блюда > 20% от нормы,
     * система автоматически увеличивает "Estimated Time" для новых клиентов.
     */
    public function calibrateKitchenPerformance(int $restaurantId)
    {
        $recentDelays = RestaurantOrder::where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subHour())
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, preparation_started_at, updated_at)) as avg_time'))
            ->first();

        $avgTime = $recentDelays->avg_time ?? 15;
        $standardTime = 15; // Норматив

        if ($avgTime > $standardTime * 1.2) {
            $penaltyFactor = ($avgTime / $standardTime);
            Log::warning("AI Adapter: Kitchen delay detected ($avgTime min). Applying penalty factor x$penaltyFactor to estimated delivery.");
            
            // Здесь мы обновляем кэш или настройки тенанта для отображения клиентам в Marketplace
            cache()->put("tenant_{$restaurantId}_prep_penalty", $penaltyFactor, 3600);
        }
    }

    /**
     * Адаптивное обучение такси на отказах (Cancellations).
     * Если водители часто отменяют заказы в зоне Surge, 
     * значит наценка недостаточно высокая для привлечения флота — корректируем алгоритм.
     */
    public function calibrateTaxiSurge(float $lat, float $lon)
    {
        $recentCancellations = TaxiTrip::where('status', 'cancelled')
            ->whereRaw('JSON_EXTRACT(origin_geo, "$.lat") BETWEEN ? AND ?', [$lat - 0.01, $lat + 0.01])
            ->where('created_at', '>=', now()->subMinutes(30))
            ->count();

        if ($recentCancellations > 10) {
            Log::info("AI Adapter: High cancellation rate in zone. Learning: increasing base surge multiplier to attract drivers.");
            
            // Автоматическая коррекция множителя в TaxiSurgeZone
            DB::table('taxi_surge_zones')
                ->where('is_active', true)
                // Упрощенный поиск зоны
                ->increment('multiplier', 0.2); 
        }
    }

    /**
     * Анализ ошибок персонала (Audit Log Analysis).
     * Если сотрудник часто совершает некорректные возвраты или удаления чеков,
     * система ставит задачу на переобучение в HR модуль.
     */
    public function analyzeStaffAnomalies(int $staffId)
    {
        $errorLogs = DB::table('audit_logs')
            ->where('user_id', $staffId)
            ->whereIn('action_type', ['void_transaction', 'delete_item', 'failed_payment'])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($errorLogs > 5) {
            // Создаем задачу в HR модуле на "Контрольное обучение"
            DB::table('staff_tasks')->insert([
                'staff_id' => $staffId,
                'title' => '🚨 Автоматическое обучение: Разбор операционных ошибок',
                'description' => 'Система зафиксировала аномальное количество ошибок при работе с кассой за последние 7 дней.',
                'status' => 'pending',
                'created_at' => now(),
            ]);
        }
    }
}
