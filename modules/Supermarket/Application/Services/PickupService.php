<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\CatCRM\Domain\Verticals\Supermarket\SupermarketOrder;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

/**
 * PickupService — Сервис управления самозабором
 * 
 * Управляет всеми этапами самозабора:
 * - Готовность к самозабору
 * - Генерация QR кода
 * - Уведомления клиенту
 * - Прибытие клиента
 * - Выдача заказа
 * - Сбор обратной связи
 */
final class PickupService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    // ========================
    // PICKUP READINESS
    // ========================

    /**
     * Отметить заказ готовым к самозабору
     */
    public function markReadyForPickup(
        SupermarketOrder $order,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd,
        ?string $pickupZone = null,
        ?string $lockerNumber = null,
        ?string $qrCode = null
    ): bool {
        return $this->withSpan(
            'supermarket_pickup.mark_ready',
            function () use ($order, $windowStart, $windowEnd, $pickupZone, $lockerNumber, $qrCode) {
                // Fraud check
                $this->fraudControl->check($order->customer->user_id ?? 0, 'pickup_ready', 0);

                $order->update([
                    'order_status' => 'pickup_ready',
                    'pickup_ready_at' => now(),
                    'pickup_window_start' => $windowStart,
                    'pickup_window_end' => $windowEnd,
                    'pickup_zone' => $pickupZone,
                    'locker_number' => $lockerNumber,
                    'qr_code' => $qrCode ?? $this->generateQRCode($order),
                ]);

                $this->logAction('pickup_ready', $order->id, [
                    'pickup_window_start' => $windowStart->toIso8601String(),
                    'pickup_window_end' => $windowEnd->toIso8601String(),
                    'pickup_zone' => $pickupZone,
                    'locker_number' => $lockerNumber,
                ], $order->customer->user_id ?? null, $order->tenant_id);

                // TODO: Send notification to customer
                // TODO: Send SMS with QR code

                return true;
            },
            $this->getStandardAttributes('supermarket', 'pickup_ready'),
        );
    }

    /**
     * Сгенерировать QR код для самозабора
     */
    private function generateQRCode(SupermarketOrder $order): string
    {
        return 'QR-' . $order->order_number . '-' . now()->timestamp;
    }

    // ========================
    // CUSTOMER ARRIVAL
    // ========================

    /**
     * Клиент прибыл на самозабор
     */
    public function markCustomerArrived(
        SupermarketOrder $order,
        string $checkInMethod,
        ?int $queueNumber = null
    ): bool {
        return $this->withSpan(
            'supermarket_pickup.customer_arrived',
            function () use ($order, $checkInMethod, $queueNumber) {
                $order->update([
                    'customer_arrived_at' => now(),
                    'order_status' => 'customer_arrived',
                ]);

                // Calculate queue wait time
                if ($queueNumber) {
                    $queueWaitTime = $queueNumber * 5; // 5 min per customer
                    $order->update(['queue_wait_time' => $queueWaitTime]);
                }

                $this->logAction('customer_arrived', $order->id, [
                    'check_in_method' => $checkInMethod,
                    'queue_number' => $queueNumber,
                ], $order->customer->user_id ?? null, $order->tenant_id);

                return true;
            },
            $this->getStandardAttributes('supermarket', 'pickup_customer_arrived'),
        );
    }

    // ========================
    // PICKUP HANDOFF
    // ========================

    /**
     * Начать выдачу заказа
     */
    public function startPickup(
        SupermarketOrder $order,
        int $operatorId
    ): bool {
        return $this->withSpan(
            'supermarket_pickup.start_pickup',
            function () use ($order, $operatorId) {
                $order->update([
                    'pickup_started_at' => now(),
                    'pickup_operator_id' => $operatorId,
                    'order_status' => 'pickup_handoff',
                ]);

                $this->logAction('pickup_started', $order->id, [
                    'operator_id' => $operatorId,
                ], $order->customer->user_id ?? null, $order->tenant_id);

                return true;
            },
            $this->getStandardAttributes('supermarket', 'pickup_start'),
        );
    }

    /**
     * Верифицировать ID клиента
     */
    public function verifyCustomerID(
        SupermarketOrder $order,
        string $documentType,
        string $documentNumber
    ): bool {
        return $this->withSpan(
            'supermarket_pickup.verify_id',
            function () use ($order, $documentType, $documentNumber) {
                // TODO: Implement actual ID verification logic
                $order->update([
                    'id_verified_at' => now(),
                ]);

                $this->logAction('id_verified', $order->id, [
                    'document_type' => $documentType,
                ], $order->customer->user_id ?? null, $order->tenant_id);

                return true;
            },
            $this->getStandardAttributes('supermarket', 'pickup_verify_id'),
        );
    }

    /**
     * Завершить выдачу заказа
     */
    public function completePickup(
        SupermarketOrder $order,
        ?string $pickupPhotoUrl = null,
        ?float $temperatureAtPickup = null
    ): bool {
        return $this->withSpan(
            'supermarket_pickup.complete_pickup',
            function () use ($order, $pickupPhotoUrl, $temperatureAtPickup) {
                $order->update([
                    'pickup_photo_url' => $pickupPhotoUrl,
                    'pickup_actual_at' => now(),
                    'order_status' => 'picked_up',
                    'fulfillment_duration' => $order->created_at->diffInMinutes(now()),
                    'on_time_delivery' => $order->pickup_window_end 
                        ? now()->lte($order->pickup_window_end) 
                        : true,
                ]);

                // Log temperature check for cold chain
                if ($order->contains_cold_chain && $temperatureAtPickup !== null) {
                    Log::info('Cold chain temperature check at pickup', [
                        'order_id' => $order->id,
                        'temperature' => $temperatureAtPickup,
                        'pickup_at' => now(),
                    ]);
                }

                $this->logAction('pickup_completed', $order->id, [
                    'temperature' => $temperatureAtPickup,
                    'on_time' => $order->on_time_delivery,
                ], $order->customer->user_id ?? null, $order->tenant_id);

                // TODO: Send confirmation notification

                return true;
            },
            $this->getStandardAttributes('supermarket', 'pickup_complete'),
        );
    }

    // ========================
    // PICKUP RATING
    // ========================

    /**
     * Оценить самозабор
     */
    public function ratePickup(
        SupermarketOrder $order,
        int $rating,
        ?string $feedback = null,
        ?array $issues = null
    ): bool {
        return $this->withSpan(
            'supermarket_pickup.rate_pickup',
            function () use ($order, $rating, $feedback, $issues) {
                $order->update([
                    'pickup_rating' => $rating,
                    'pickup_feedback' => $feedback,
                    'pickup_issues' => $issues,
                ]);

                $this->logAction('pickup_rated', $order->id, [
                    'rating' => $rating,
                ], $order->customer->user_id ?? null, $order->tenant_id);

                return true;
            },
            $this->getStandardAttributes('supermarket', 'pickup_rate'),
        );
    }

    // ========================
    // ANALYTICS
    // ========================

    /**
     * Получить статистику самозабора за период
     */
    public function getPickupStatistics(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_pickup.get_statistics',
            function () use ($tenantId, $businessGroupId, $days) {
                $query = SupermarketOrder::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where('delivery_type', 'pickup')
                    ->where('created_at', '>=', now()->subDays($days));

                $totalPickups = $query->count();
                $completedPickups = $query->where('order_status', 'picked_up')->count();
                $onTimePickups = $query->where('on_time_delivery', true)->count();
                $avgRating = $query->whereNotNull('pickup_rating')->avg('pickup_rating');
                $avgWaitTime = $query->whereNotNull('queue_wait_time')->avg('queue_wait_time');
                $avgFulfillmentTime = $query->whereNotNull('fulfillment_duration')->avg('fulfillment_duration');

                // Calculate expired pickups (not picked up within window)
                $expiredPickups = $query
                    ->where('order_status', '!=', 'picked_up')
                    ->where('pickup_window_end', '<', now())
                    ->count();

                return [
                    'total_pickups' => $totalPickups,
                    'completed_pickups' => $completedPickups,
                    'completion_rate' => $totalPickups > 0 ? round($completedPickups / $totalPickups * 100, 2) : 0,
                    'on_time_pickups' => $onTimePickups,
                    'on_time_rate' => $completedPickups > 0 ? round($onTimePickups / $completedPickups * 100, 2) : 0,
                    'expired_pickups' => $expiredPickups,
                    'avg_pickup_rating' => round($avgRating ?? 0, 2),
                    'avg_queue_wait_time_minutes' => round($avgWaitTime ?? 0, 2),
                    'avg_fulfillment_time_minutes' => round($avgFulfillmentTime ?? 0, 2),
                    'period_days' => $days,
                ];
            },
            $this->getStandardAttributes('supermarket', 'pickup_statistics'),
        );
    }

    /**
     * Получить заказы готовые к самозабору
     */
    public function getReadyForPickup(int $tenantId, ?int $businessGroupId = null): array
    {
        return SupermarketOrder::where('tenant_id', $tenantId)
            ->where('business_group_id', $businessGroupId)
            ->where('delivery_type', 'pickup')
            ->where('order_status', 'pickup_ready')
            ->where('pickup_window_end', '>=', now())
            ->with('customer')
            ->get()
            ->map(fn($order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer->getDisplayName(),
                'customer_phone' => $order->customer->phone,
                'pickup_window_start' => $order->pickup_window_start?->toIso8601String(),
                'pickup_window_end' => $order->pickup_window_end?->toIso8601String(),
                'pickup_zone' => $order->pickup_zone,
                'locker_number' => $order->locker_number,
                'qr_code' => $order->qr_code,
                'items_count' => $order->items_count,
                'contains_cold_chain' => $order->contains_cold_chain,
            ])
            ->toArray();
    }

    /**
     * Получить просроченные самозаборы
     */
    public function getExpiredPickups(int $tenantId, ?int $businessGroupId = null): array
    {
        return SupermarketOrder::where('tenant_id', $tenantId)
            ->where('business_group_id', $businessGroupId)
            ->where('delivery_type', 'pickup')
            ->where('order_status', '!=', 'picked_up')
            ->where('pickup_window_end', '<', now())
            ->with('customer')
            ->get()
            ->map(fn($order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer->getDisplayName(),
                'customer_phone' => $order->customer->phone,
                'pickup_window_end' => $order->pickup_window_end?->toIso8601String(),
                'expired_hours_ago' => $order->pickup_window_end->diffInHours(now()),
                'total_amount' => $order->total_amount,
                'contains_perishable' => $order->contains_perishable,
            ])
            ->toArray();
    }
}
