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
 * DeliveryTrackingService — Сервис отслеживания доставки курьеров
 * 
 * Управляет всеми этапами доставки курьером:
 * - Назначение курьера
 * - Отслеживание локации
 * - Обновление ETA
 * - Уведомления клиенту
 * - Передача заказа
 * - Сбор обратной связи
 */
final class DeliveryTrackingService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    // ========================
    // COURIER ASSIGNMENT
    // ========================

    /**
     * Назначить курьера на заказ
     */
    public function assignCourier(
        SupermarketOrder $order,
        int $courierId,
        string $courierName,
        string $courierPhone,
        string $vehicleType,
        ?array $route = null
    ): bool {
        return $this->withSpan(
            'supermarket_delivery.assign_courier',
            function () use ($order, $courierId, $courierName, $courierPhone, $vehicleType, $route) {
                // Fraud check
                $this->fraudControl->check($order->customer->user_id ?? 0, 'courier_assign', 0);

                $order->update([
                    'courier_id' => $courierId,
                    'courier_name' => $courierName,
                    'courier_phone' => $courierPhone,
                    'courier_vehicle_type' => $vehicleType,
                    'delivery_route' => $route,
                    'courier_assigned_at' => now(),
                    'order_status' => 'courier_assigned',
                ]);

                $this->logAction('courier_assigned', $order->id, null, $order->customer->user_id ?? null, $order->tenant_id);

                // TODO: Send notification to customer
                // TODO: Send notification to courier

                return true;
            },
            $this->getStandardAttributes('supermarket', 'delivery_assign_courier'),
        );
    }

    // ========================
    // LOCATION TRACKING
    // ========================

    /**
     * Обновить локацию курьера
     */
    public function updateCourierLocation(
        SupermarketOrder $order,
        float $lat,
        float $lng,
        ?int $progress = null
    ): bool {
        return $this->withSpan(
            'supermarket_delivery.update_location',
            function () use ($order, $lat, $lng, $progress) {
                $order->update([
                    'courier_location_lat' => $lat,
                    'courier_location_lng' => $lng,
                    'courier_location_updated_at' => now(),
                    'delivery_progress' => $progress ?? $order->delivery_progress,
                ]);

                if ($order->order_status === 'courier_assigned') {
                    $order->update(['order_status' => 'courier_en_route']);
                }

                // TODO: Send real-time update to customer

                return true;
            },
            $this->getStandardAttributes('supermarket', 'delivery_update_location'),
        );
    }

    /**
     * Обновить ETA
     */
    public function updateDeliveryETA(
        SupermarketOrder $order,
        CarbonImmutable $eta
    ): bool {
        return $this->withSpan(
            'supermarket_delivery.update_eta',
            function () use ($order, $eta) {
                $order->update([
                    'delivery_eta' => $eta,
                ]);

                // TODO: Send ETA update to customer

                return true;
            },
            $this->getStandardAttributes('supermarket', 'delivery_update_eta'),
        );
    }

    // ========================
    // COURIER ARRIVAL
    // ========================

    /**
     * Курьер прибыл на точку
     */
    public function markCourierArrived(SupermarketOrder $order): bool
    {
        return $this->withSpan(
            'supermarket_delivery.courier_arrived',
            function () use ($order) {
                $order->update([
                    'courier_arrived_at' => now(),
                    'order_status' => 'courier_arrived',
                ]);

                // TODO: Send notification to customer
                // TODO: Start waiting timer

                return true;
            },
            $this->getStandardAttributes('supermarket', 'delivery_courier_arrived'),
        );
    }

    // ========================
    // DELIVERY HANDOFF
    // ========================

    /**
     * Передать заказ клиенту
     */
    public function handoffOrder(
        SupermarketOrder $order,
        string $handoffMethod,
        ?string $deliveryPhotoUrl = null,
        ?string $clientSignatureUrl = null,
        ?float $finalTemperature = null
    ): bool {
        return $this->withSpan(
            'supermarket_delivery.handoff_order',
            function () use ($order, $handoffMethod, $deliveryPhotoUrl, $clientSignatureUrl, $finalTemperature) {
                $order->update([
                    'handoff_method' => $handoffMethod,
                    'delivery_photo_url' => $deliveryPhotoUrl,
                    'client_signature_url' => $clientSignatureUrl,
                    'handoff_at' => now(),
                    'order_status' => 'delivery_handoff',
                ]);

                // Log temperature check for cold chain
                if ($order->contains_cold_chain && $finalTemperature !== null) {
                    Log::info('Cold chain temperature check', [
                        'order_id' => $order->id,
                        'temperature' => $finalTemperature,
                        'handoff_at' => now(),
                    ]);
                }

                $this->logAction('delivery_handoff', $order->id, [
                    'handoff_method' => $handoffMethod,
                    'temperature' => $finalTemperature,
                ], $order->customer->user_id ?? null, $order->tenant_id);
tl->getStandardAttributes('supermarket', 'delivery_handoff'),
        );
    }

    // ========================
    // DELIVERY COMPLETION
    // ========================

    /**
     * Завершить доставку
     */
    public function completeDelivery(
        SupermarketOrder $order,
        ?int $deliveryRating = null,
        ?string $deliveryFeedback = null,
        ?int $courierRating = null,
        ?array $deliveryIssues = null
    ): bool {
        return $this->withSpan(
            'supermarket_delivery.complete_delivery',
            function () use ($order, $deliveryRating, $deliveryFeedback, $courierRating, $deliveryIssues) {
                $order->update([
                    'delivery_actual_at' => now(),
                    'order_status' => 'delivered',
                    'delivery_rating' => $deliveryRating,
                    'delivery_feedback' => $deliveryFeedback,
                    'courier_rating' => $courierRating,
                    'delivery_issues' => $deliveryIssues,
                    'fulfillment_duration' => $order->created_at->diffInMinutes(now()),
                    'on_time_delivery' => $order->delivery_eta ? now()->lte($order->delivery_eta) : true,
                ]);

                $this->logAction('delivery_completed', $order->id, [
                    'rating' => $deliveryRating,
                    'courier_rating' => $courierRating,
                    'on_time' => $order->on_time_delivery,
                ], $order->customer->user_id ?? null, $order->tenant_id);

                // TODO: Update customer statistics
                // TODO: Update courier statisticstl->getStandardAttributes('supermarket', 'delivery_complete'),
        );
    }

    // ========================
    // ANALYTICS
    // ========================

    /**
     * Получить статистику доставки за период
     */
    public function getDeliveryStatistics(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_delivery.get_statistics',
            function () use ($tenantId, $businessGroupId, $days) {
                $query = SupermarketOrder::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where('delivery_type', 'courier')
                    ->where('created_at', '>=', now()->subDays($days));

                $totalDeliveries = $query->count();
                $completedDeliveries = $query->where('order_status', 'delivered')->count();
                $onTimeDeliveries = $query->where('on_time_delivery', true)->count();
                $avgRating = $query->whereNotNull('delivery_rating')->avg('delivery_rating');
                $avgCourierRating = $query->whereNotNull('courier_rating')->avg('courier_rating');
                $avgFulfillmentTime = $query->whereNotNull('fulfillment_duration')->avg('fulfillment_duration');

                return [
                    'total_deliveries' => $totalDeliveries,
                    'completed_deliveries' => $completedDeliveries,
                    'completion_rate' => $totalDeliveries > 0 ? round($completedDeliveries / $totalDeliveries * 100, 2) : 0,
                    'on_time_deliveries' => $onTimeDeliveries,
                    'on_time_rate' => $completedDeliveries > 0 ? round($onTimeDeliveries / $completedDeliveries * 100, 2) : 0,
                    'avg_delivery_rating' => round($avgRating ?? 0, 2),
                    'avg_courier_rating' => round($avgCourierRating ?? 0, 2),
                    'avg_fulfillment_time_minutes' => round($avgFulfillmentTime ?? 0, 2),
                    'period_days' => $days,
                ];
            },
            $this->getStandardAttributes('supermarket', 'delivery_statistics'),
        );
    }

    /**
     * Получить активные доставки
     */
    public function getActiveDeliveries(int $tenantId, ?int $businessGroupId = null): array
    {
        return SupermarketOrder::where('tenant_id', $tenantId)
            ->where('business_group_id', $businessGroupId)
            ->where('delivery_type', 'courier')
            ->whereIn('order_status', ['courier_assigned', 'courier_en_route', 'courier_arrived'])
            ->with('customer')
            ->get()
            ->map(fn($order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer->getDisplayName(),
                'courier_name' => $order->courier_name,
                'courier_phone' => $order->courier_phone,
                'status' => $order->order_status,
                'courier_location' => [
                    'lat' => $order->courier_location_lat,
                    'lng' => $order->courier_location_lng,
                    'updated_at' => $order->courier_location_updated_at?->toIso8601String(),
                ],
                'delivery_eta' => $order->delivery_eta?->toIso8601String(),
                'delivery_progress' => $order->delivery_progress,
                'delivery_address' => $order->delivery_address,
            ])
            ->toArray();
    }
}
