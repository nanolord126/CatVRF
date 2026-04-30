<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Adapters;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * ColdChainAdapter - мониторинг холодовой цепи для скоропортящихся товаров.
 *
 * Отслеживает температурный режим для продуктов требующих охлаждения:
 * - Мясные изделия
 * - Молочные продукты
 - Рыба и морепродукты
 * - Свежие овощи и фрукты
 * - Замороженные продукты
 */
final readonly class ColdChainAdapter
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Проверить, требуется ли холодовая цепь для товаров.
     *
     * @param  array<int, array{product_id: int, category: string, requires_cold_chain: bool}>  $items
     * @return bool
     */
    public function isColdChainRequired(array $items): bool
    {
        $coldChainCategories = config('verticals.supermarket.cold_chain.categories', [
            'meat',
            'dairy',
            'fish',
            'frozen',
            'fresh_vegetables',
            'fresh_fruits',
        ]);

        foreach ($items as $item) {
            $category = $item['category'] ?? null;
            $requiresColdChain = $item['requires_cold_chain'] ?? false;

            if ($requiresColdChain || in_array($category, $coldChainCategories, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Зарегистрировать заказ для мониторинга холодовой цепи.
     *
     * @param  int  $orderId
     * @param  array{items: array, delivery_eta: int, correlation_id: string}  $data
     * @return void
     */
    public function registerOrderForMonitoring(int $orderId, array $data): void
    {
        $correlationId = $data['correlation_id'] ?? '';
        $deliveryEta = $data['delivery_eta'] ?? 60; // minutes

        $this->db->table('supermarket_cold_chain_monitoring')->insert([
            'order_id' => $orderId,
            'status' => 'monitoring',
            'max_allowed_temp' => config('verticals.supermarket.cold_chain.max_temp', 5),
            'min_allowed_temp' => config('verticals.supermarket.cold_chain.min_temp', -18),
            'delivery_eta_minutes' => $deliveryEta,
            'last_check_at' => now(),
            'correlation_id' => $correlationId,
            'created_at' => now(),
        ]);

        // Cache for quick lookup
        Cache::put("cold_chain:order:{$orderId}", true, now()->addHours(2));

        $this->logger->info('Cold chain monitoring registered', [
            'order_id' => $orderId,
            'correlation_id' => $correlationId,
            'delivery_eta' => $deliveryEta,
        ]);
    }

    /**
     * Снять заказ с мониторинга холодовой цепи.
     *
     * @param  int  $orderId
     * @return void
     */
    public function unregisterOrderFromMonitoring(int $orderId): void
    {
        $this->db->table('supermarket_cold_chain_monitoring')
            ->where('order_id', $orderId)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        Cache::forget("cold_chain:order:{$orderId}");

        $this->logger->info('Cold chain monitoring unregistered', [
            'order_id' => $orderId,
        ]);
    }

    /**
     * Записать температурные данные для заказа.
     *
     * @param  int  $orderId
     * @param  float  $temperature
     * @param  string  $sensorId
     * @return void
     */
    public function recordTemperature(int $orderId, float $temperature, string $sensorId): void
    {
        $this->db->table('supermarket_cold_chain_temperatures')->insert([
            'order_id' => $orderId,
            'sensor_id' => $sensorId,
            'temperature' => $temperature,
            'recorded_at' => now(),
        ]);

        // Check for violations
        $monitoring = $this->db->table('supermarket_cold_chain_monitoring')
            ->where('order_id', $orderId)
            ->first();

        if ($monitoring) {
            if ($temperature > $monitoring->max_allowed_temp || $temperature < $monitoring->min_allowed_temp) {
                $this->recordViolation($orderId, $temperature, $sensorId);
            }
        }
    }

    /**
     * Записать нарушение температурного режима.
     *
     * @param  int  $orderId
     * @param  float  $temperature
     * @param  string  $sensorId
     * @return void
     */
    private function recordViolation(int $orderId, float $temperature, string $sensorId): void
    {
        $this->db->table('supermarket_cold_chain_violations')->insert([
            'order_id' => $orderId,
            'sensor_id' => $sensorId,
            'temperature' => $temperature,
            'violation_type' => $temperature > 5 ? 'high' : 'low',
            'severity' => 'critical',
            'created_at' => now(),
        ]);

        $this->logger->warning('Cold chain violation detected', [
            'order_id' => $orderId,
            'temperature' => $temperature,
            'sensor_id' => $sensorId,
        ]);
    }

    /**
     * Получить статус холодовой цепи для заказа.
     *
     * @param  int  $orderId
     * @return array{status: string, violations_count: int, last_temperature: float|null}
     */
    public function getColdChainStatus(int $orderId): array
    {
        $monitoring = $this->db->table('supermarket_cold_chain_monitoring')
            ->where('order_id', $orderId)
            ->first();

        if (!$monitoring) {
            return [
                'status' => 'not_monitored',
                'violations_count' => 0,
                'last_temperature' => null,
            ];
        }

        $violationsCount = $this->db->table('supermarket_cold_chain_violations')
            ->where('order_id', $orderId)
            ->count();

        $lastTemperature = $this->db->table('supermarket_cold_chain_temperatures')
            ->where('order_id', $orderId)
            ->orderBy('recorded_at', 'desc')
            ->value('temperature');

        return [
            'status' => $monitoring->status,
            'violations_count' => $violationsCount,
            'last_temperature' => $lastTemperature,
            'max_allowed_temp' => $monitoring->max_allowed_temp,
            'min_allowed_temp' => $monitoring->min_allowed_temp,
        ];
    }
}
