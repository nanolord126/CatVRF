<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\OrderFulfillment;

final readonly class OrderHandoffControlService
{
    use WithAuditLogging;

    private const CACHE_TTL = 600;

    public function __construct(
        private AuditService $auditService
    ) {}

    public function initiateHandoff(
        int $fulfillmentId,
        int $waiterId,
        ?string $userId = null,
        ?string $tenantId = null
    ): OrderFulfillment {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);

        if ($fulfillment->status !== 'ready') {
            throw new \RuntimeException('Order must be ready before handoff');
        }

        $fulfillment->update([
            'assigned_waiter_id' => $waiterId,
            'handoff_initiated_at' => now(),
            'status' => 'handoff_in_progress',
        ]);

        $this->auditService->logAction(
            action: 'restaurant.order_handoff_initiated',
            entityType: 'OrderFulfillment',
            entityId: $fulfillmentId,
            userId: $userId,
            tenantId: $tenantId ?? $fulfillment->tenant_id,
            context: [
                'waiter_id' => $waiterId,
                'order_id' => $fulfillment->order_id,
                'restaurant_id' => $fulfillment->restaurant_id,
            ]
        );

        $this->invalidateKDSCache($fulfillment->restaurant_id);

        return $fulfillment->fresh();
    }

    public function confirmPickup(
        int $fulfillmentId,
        int $waiterId,
        ?array $itemsVerified = null,
        ?string $userId = null,
        ?string $tenantId = null
    ): OrderFulfillment {
        return DB::transaction(function () use ($fulfillmentId, $waiterId, $itemsVerified, $userId, $tenantId) {
            $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);

            if ($fulfillment->status !== 'handoff_in_progress') {
                throw new \RuntimeException('Order handoff must be in progress');
            }

            if ($fulfillment->assigned_waiter_id !== $waiterId) {
                throw new \RuntimeException('Waiter mismatch');
            }

            $fulfillment->update([
                'picked_up_at' => now(),
                'status' => 'picked_up',
                'items_verified' => $itemsVerified ?? $fulfillment->items,
            ]);

            $this->auditService->logAction(
                action: 'restaurant.order_picked_up',
                entityType: 'OrderFulfillment',
                entityId: $fulfillmentId,
                userId: $userId,
                tenantId: $tenantId ?? $fulfillment->tenant_id,
                context: [
                    'waiter_id' => $waiterId,
                    'order_id' => $fulfillment->order_id,
                    'items_verified' => $itemsVerified,
                ]
            );

            return $fulfillment->fresh();
        });
    }

    public function confirmDelivery(
        int $fulfillmentId,
        int $tableNumber,
        ?int $customerRating = null,
        ?string $feedback = null,
        ?string $userId = null,
        ?string $tenantId = null
    ): OrderFulfillment {
        return DB::transaction(function () use ($fulfillmentId, $tableNumber, $customerRating, $feedback, $userId, $tenantId) {
            $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);

            if ($fulfillment->status !== 'picked_up') {
                throw new \RuntimeException('Order must be picked up before delivery');
            }

            $fulfillment->update([
                'delivered_at' => now(),
                'status' => 'served',
                'table_number' => $tableNumber,
                'customer_rating' => $customerRating,
                'customer_feedback' => $feedback,
            ]);

            $handoffTime = $fulfillment->handoff_initiated_at 
                ? $fulfillment->delivered_at->diffInMinutes($fulfillment->handoff_initiated_at)
                : null;

            $this->auditService->logAction(
                action: 'restaurant.order_delivered',
                entityType: 'OrderFulfillment',
                entityId: $fulfillmentId,
                userId: $userId,
                tenantId: $tenantId ?? $fulfillment->tenant_id,
                context: [
                    'table_number' => $tableNumber,
                    'customer_rating' => $customerRating,
                    'handoff_duration_minutes' => $handoffTime,
                ]
            );

            return $fulfillment->fresh();
        });
    }

    public function reportIssue(
        int $fulfillmentId,
        string $issueType,
        string $description,
        ?array $photos = null,
        ?string $userId = null,
        ?string $tenantId = null
    ): OrderFulfillment {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);

        $issues = $fulfillment->issues ?? [];
        $issues[] = [
            'type' => $issueType,
            'description' => $description,
            'photos' => $photos,
            'reported_at' => now()->toIso8601String(),
            'reported_by' => $userId,
        ];

        $fulfillment->update([
            'issues' => $issues,
            'status' => 'issue_reported',
        ]);

        $this->auditService->logAction(
            action: 'restaurant.order_issue_reported',
            entityType: 'OrderFulfillment',
            entityId: $fulfillmentId,
            userId: $userId,
            tenantId: $tenantId ?? $fulfillment->tenant_id,
            context: [
                'issue_type' => $issueType,
                'description' => $description,
            ]
        );

        return $fulfillment->fresh();
    }

    public function getHandoffBoard(int $restaurantId): array
    {
        $cacheKey = "handoff_board_{$restaurantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($restaurantId) {
            $orders = OrderFulfillment::where('restaurant_id', $restaurantId)
                ->whereIn('status', ['ready', 'handoff_in_progress', 'picked_up'])
                ->with(['order', 'assignedWaiter'])
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'asc')
                ->get();

            return [
                'ready_for_handoff' => $orders->where('status', 'ready')->map(fn($o) => [
                    'id' => $o->id,
                    'order_id' => $o->order_id,
                    'order_type' => $o->order_type,
                    'table_number' => $o->order->table_number ?? null,
                    'items' => $o->items,
                    'special_instructions' => $o->special_instructions,
                    'ready_at' => $o->ready_at?->format('H:i:s'),
                    'wait_time' => $o->ready_at ? now()->diffInMinutes($o->ready_at) : null,
                ])->toArray(),
                'in_handoff' => $orders->where('status', 'handoff_in_progress')->map(fn($o) => [
                    'id' => $o->id,
                    'order_id' => $o->order_id,
                    'waiter_id' => $o->assigned_waiter_id,
                    'waiter_name' => $o->assignedWaiter?->name,
                    'handoff_initiated_at' => $o->handoff_initiated_at?->format('H:i:s'),
                    'duration' => $o->handoff_initiated_at ? now()->diffInMinutes($o->handoff_initiated_at) : null,
                ])->toArray(),
                'picked_up' => $orders->where('status', 'picked_up')->map(fn($o) => [
                    'id' => $o->id,
                    'order_id' => $o->order_id,
                    'waiter_id' => $o->assigned_waiter_id,
                    'waiter_name' => $o->assignedWaiter?->name,
                    'picked_up_at' => $o->picked_up_at?->format('H:i:s'),
                    'duration' => $o->picked_up_at ? now()->diffInMinutes($o->picked_up_at) : null,
                ])->toArray(),
            ];
        });
    }

    public function getWaiterWorkload(
        int $waiterId,
        CarbonImmutable $date
    ): array {
        $orders = OrderFulfillment::where('assigned_waiter_id', $waiterId)
            ->whereDate('created_at', $date)
            ->get();

        return [
            'waiter_id' => $waiterId,
            'date' => $date->format('Y-m-d'),
            'total_orders' => $orders->count(),
            'ready_for_pickup' => $orders->where('status', 'ready')->count(),
            'in_handoff' => $orders->where('status', 'handoff_in_progress')->count(),
            'picked_up' => $orders->where('status', 'picked_up')->count(),
            'delivered' => $orders->where('status', 'served')->count(),
            'average_handoff_time' => $orders->whereNotNull('picked_up_at')
                ->whereNotNull('handoff_initiated_at')
                ->map(fn($o) => $o->picked_up_at->diffInMinutes($o->handoff_initiated_at))
                ->avg(),
            'average_delivery_time' => $orders->whereNotNull('delivered_at')
                ->whereNotNull('picked_up_at')
                ->map(fn($o) => $o->delivered_at->diffInMinutes($o->picked_up_at))
                ->avg(),
        ];
    }

    public function getHandoffAnalytics(
        int $restaurantId,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate
    ): array {
        $orders = OrderFulfillment::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        return [
            'period' => [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d'),
            ],
            'total_orders' => $orders->count(),
            'by_status' => [
                'ready' => $orders->where('status', 'ready')->count(),
                'handoff_in_progress' => $orders->where('status', 'handoff_in_progress')->count(),
                'picked_up' => $orders->where('status', 'picked_up')->count(),
                'served' => $orders->where('status', 'served')->count(),
            ],
            'by_order_type' => [
                'b2b' => $orders->where('order_type', 'b2b')->count(),
                'b2c' => $orders->where('order_type', 'b2c')->count(),
            ],
            'performance' => [
                'average_handoff_time' => $orders->whereNotNull('picked_up_at')
                    ->whereNotNull('handoff_initiated_at')
                    ->map(fn($o) => $o->picked_up_at->diffInMinutes($o->handoff_initiated_at))
                    ->avg(),
                'average_delivery_time' => $orders->whereNotNull('delivered_at')
                    ->whereNotNull('picked_up_at')
                    ->map(fn($o) => $o->delivered_at->diffInMinutes($o->picked_up_at))
                    ->avg(),
                'average_rating' => $orders->whereNotNull('customer_rating')->avg('customer_rating'),
                'issues_count' => $orders->where('status', 'issue_reported')->count(),
            ],
            'waiter_performance' => $orders->whereNotNull('assigned_waiter_id')
                ->groupBy('assigned_waiter_id')
                ->map(function ($waiterOrders) {
                    return [
                        'total_orders' => $waiterOrders->count(),
                        'average_rating' => $waiterOrders->whereNotNull('customer_rating')->avg('customer_rating'),
                        'issues' => $waiterOrders->where('status', 'issue_reported')->count(),
                    ];
                }),
        ];
    }

    public function autoAssignWaiters(
        int $restaurantId,
        ?string $userId = null,
        ?string $tenantId = null
    ): array {
        $readyOrders = OrderFulfillment::where('restaurant_id', $restaurantId)
            ->where('status', 'ready')
            ->whereNull('assigned_waiter_id')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        $availableWaiters = $this->getAvailableWaiters($restaurantId);

        $assignments = [];
        $waiterIndex = 0;

        foreach ($readyOrders as $order) {
            if ($availableWaiters->isEmpty()) {
                break;
            }

            $waiter = $availableWaiters[$waiterIndex];
            
            try {
                $this->initiateHandoff($order->id, $waiter->id, $userId, $tenantId);
                
                $assignments[] = [
                    'order_id' => $order->order_id,
                    'waiter_id' => $waiter->id,
                    'fulfillment_id' => $order->id,
                ];

                $waiterIndex = ($waiterIndex + 1) % $availableWaiters->count();
            } catch (\Exception $e) {
                Log::error('Failed to auto-assign waiter', [
                    'fulfillment_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $assignments;
    }

    private function getAvailableWaiters(int $restaurantId)
    {
        $activeAssignments = OrderFulfillment::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['handoff_in_progress', 'picked_up'])
            ->pluck('assigned_waiter_id')
            ->unique()
            ->toArray();

        return \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'restaurant_waiter'))
            ->where('is_active', true)
            ->whereNotIn('id', $activeAssignments)
            ->get();
    }

    private function invalidateKDSCache(int $restaurantId): void
    {
        $cacheKey = "handoff_board_{$restaurantId}";
        Cache::forget($cacheKey);
    }
}
