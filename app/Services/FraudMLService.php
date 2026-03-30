<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FraudMLService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Скоринг заказ-наряда СТО.
         * Ищет признаки "завышения цен" или "фиктивных работ".
         */
        public function scoreRepairOrder(AutoRepairOrder $order, string $correlationId): float
        {
            $score = 0.0;

            // 1. Слишком высокая стоимость работ для модели авто
            if ($order->labor_cost_kopecks > 5000000) { // > 50к руб
                $score += 0.3;
            }

            // 2. Отсутствие фото при высокой стоимости (если AI Vision не использовался)
            if (empty($order->ai_estimate) && $order->total_cost_kopecks > 10000000) {
                $score += 0.2;
            }

            // 3. Слишком частые ремонты одного авто (раз в 3 дня)
            $previousRepairs = AutoRepairOrder::where('vehicle_id', $order->vehicle_id)
                ->where('created_at', '>', now()->subDays(3))
                ->count();
            if ($previousRepairs > 1) {
                $score += 0.4;
            }

            Log::channel('fraud_alert')->info('Repair Order Fraud Score', [
                'order_uuid' => $order->uuid,
                'score' => $score,
                'correlation_id' => $correlationId,
            ]);

            return min($score, 1.0);
        }

        /**
         * Скоринг поездки Такси.
         * Ищет "самовыкупы" или "накрутку рейтинга".
         */
        public function scoreTaxiRide(TaxiRide $ride, string $correlationId): float
        {
            $score = 0.0;

            // 1. Пассажир и водитель — один и тот же бизнес-аккаунт
            if ($ride->vehicle && $ride->vehicle->tenant_id === tenant()->id) {
                // Риск самовыкупа внутри тенанта
                $score += 0.3;
            }

            // 2. Слишком короткая поездка (< 300 метров) при высокой цене
            // (Логика на GEOLocation)

            // 3. Использование одного устройства (fingerprint)

            Log::channel('fraud_alert')->info('Taxi Ride Fraud Score', [
                'ride_uuid' => $ride->uuid,
                'score' => $score,
                'correlation_id' => $correlationId,
            ]);

            return min($score, 1.0);
        }
}
