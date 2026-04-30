<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Repositories\SupermarketOrderRepositoryInterface;
use Modules\CatCRM\Domain\Verticals\Supermarket\SupermarketOrder;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of Supermarket Order Repository
 */
final class EloquentSupermarketOrderRepository implements SupermarketOrderRepositoryInterface
{
    public function findById(int $id): ?SupermarketOrder
    {
        return SupermarketOrder::find($id);
    }

    public function findByUuid(string $uuid): ?SupermarketOrder
    {
        return SupermarketOrder::where('uuid', $uuid)->first();
    }

    public function findByOrderNumber(string $orderNumber): ?SupermarketOrder
    {
        return SupermarketOrder::where('order_number', $orderNumber)->first();
    }

    public function findByDealId(int $dealId): Collection
    {
        return SupermarketOrder::where('deal_id', $dealId)->get();
    }

    public function findByCustomerId(int $customerId, int $limit = 50): Collection
    {
        return SupermarketOrder::where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function findByTenantId(int $tenantId, ?int $businessGroupId = null, int $limit = 100): Collection
    {
        $query = SupermarketOrder::where('tenant_id', $tenantId);

        if ($businessGroupId !== null) {
            $query->where('business_group_id', $businessGroupId);
        }

        return $query->orderByDesc('created_at')->limit($limit)->get();
    }

    public function findByType(string $type, int $tenantId, ?int $businessGroupId = null): Collection
    {
        $query = SupermarketOrder::where('tenant_id', $tenantId)
            ->where('order_type', $type);

        if ($businessGroupId !== null) {
            $query->where('business_group_id', $businessGroupId);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function findByStatus(string $status, int $tenantId, ?int $businessGroupId = null): Collection
    {
        $query = SupermarketOrder::where('tenant_id', $tenantId)
            ->where('order_status', $status);

        if ($businessGroupId !== null) {
            $query->where('business_group_id', $businessGroupId);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function findSubscriptions(int $tenantId, ?int $businessGroupId = null): Collection
    {
        return $this->findByType('subscription', $tenantId, $businessGroupId);
    }

    public function findReturns(int $tenantId, ?int $businessGroupId = null): Collection
    {
        return $this->findByType('return', $tenantId, $businessGroupId);
    }

    public function findRequiringAgeVerification(int $tenantId, ?int $businessGroupId = null): Collection
    {
        $query = SupermarketOrder::where('tenant_id', $tenantId)
            ->where('age_verification_required', true)
            ->where('age_verification_status', 'pending');

        if ($businessGroupId !== null) {
            $query->where('business_group_id', $businessGroupId);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function findWithHonestyMarks(int $tenantId, ?int $businessGroupId = null): Collection
    {
        $query = SupermarketOrder::where('tenant_id', $tenantId)
            ->where('contains_honesty_marks', true);

        if ($businessGroupId !== null) {
            $query->where('business_group_id', $businessGroupId);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function findUpcomingDeliveries(int $tenantId, ?int $businessGroupId = null, int $hours = 24): Collection
    {
        $query = SupermarketOrder::where('tenant_id', $tenantId)
            ->where('delivery_scheduled_at', '>=', now())
            ->where('delivery_scheduled_at', '<=', now()->addHours($hours))
            ->whereIn('order_status', ['confirmed', 'processing']);

        if ($businessGroupId !== null) {
            $query->where('business_group_id', $businessGroupId);
        }

        return $query->orderBy('delivery_scheduled_at')->get();
    }

    public function findTodayOrders(int $tenantId, ?int $businessGroupId = null): Collection
    {
        $query = SupermarketOrder::where('tenant_id', $tenantId)
            ->whereDate('created_at', today());

        if ($businessGroupId !== null) {
            $query->where('business_group_id', $businessGroupId);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function create(array $data): SupermarketOrder
    {
        return SupermarketOrder::create($data);
    }

    public function update(SupermarketOrder $order, array $data): bool
    {
        return $order->update($data);
    }

    public function delete(SupermarketOrder $order): bool
    {
        return $order->delete();
    }

    public function getStatistics(int $tenantId, ?int $businessGroupId = null): array
    {
        $query = SupermarketOrder::where('tenant_id', $tenantId);

        if ($businessGroupId !== null) {
            $query->where('business_group_id', $businessGroupId);
        }

        return [
            'total_orders' => $query->count(),
            'one_time_orders' => (clone $query)->where('order_type', 'one_time')->count(),
            'subscriptions' => (clone $query)->where('order_type', 'subscription')->count(),
            'returns' => (clone $query)->where('order_type', 'return')->count(),
            'age_restricted' => (clone $query)->where('is_age_restricted', true)->count(),
            'with_honesty_marks' => (clone $query)->where('contains_honesty_marks', true)->count(),
            'total_revenue' => (clone $query)->sum('total_amount'),
            'pending' => (clone $query)->where('order_status', 'pending')->count(),
            'processing' => (clone $query)->where('order_status', 'processing')->count(),
            'completed' => (clone $query)->where('order_status', 'completed')->count(),
            'cancelled' => (clone $query)->where('order_status', 'cancelled')->count(),
        ];
    }
}
