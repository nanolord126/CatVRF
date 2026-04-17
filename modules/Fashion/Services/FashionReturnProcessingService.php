<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionReturnProcessingService
{
    private const CACHE_TTL = 1800;

    /**
     * Process return request
     */
    public function processReturnRequest(int $orderId, int $productId, string $reason, string $condition, int $userId, int $tenantId): array
    {
        return DB::transaction(function () use ($orderId, $productId, $reason, $condition, $userId, $tenantId) {
            // Check if order exists and is eligible for return
            $order = DB::table('fashion_orders')
                ->where('id', $orderId)
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'delivered')
                ->where('delivered_at', '>=', Carbon::now()->subDays(30))
                ->first();

            if (!$order) {
                return ['success' => false, 'message' => 'Order not eligible for return'];
            }

            // Check if return already exists
            $existingReturn = DB::table('fashion_returns')
                ->where('order_id', $orderId)
                ->where('product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->where('status', '!=', 'rejected')
                ->first();

            if ($existingReturn) {
                return ['success' => false, 'message' => 'Return already requested'];
            }

            // Create return request
            $returnId = uniqid();
            DB::table('fashion_returns')->insert([
                'id' => $returnId,
                'order_id' => $orderId,
                'product_id' => $productId,
                'user_id' => $userId,
                'reason' => $reason,
                'condition' => $condition,
                'status' => 'requested',
                'requested_at' => Carbon::now(),
                'tenant_id' => $tenantId,
                'created_at' => Carbon::now(),
            ]);

            Log::info('Return request created', [
                'return_id' => $returnId,
                'order_id' => $orderId,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'return_id' => $returnId,
                'message' => 'Return request submitted successfully',
            ];
        });
    }

    /**
     * Approve return
     */
    public function approveReturn(int $returnId, int $tenantId): bool
    {
        return DB::transaction(function () use ($returnId, $tenantId) {
            $return = DB::table('fashion_returns')
                ->where('id', $returnId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'requested')
                ->lockForUpdate()
                ->first();

            if (!$return) {
                return false;
            }

            // Update return status
            DB::table('fashion_returns')
                ->where('id', $returnId)
                ->update([
                    'status' => 'approved',
                    'approved_at' => Carbon::now(),
                ]);

            // Restore stock
            DB::table('fashion_products')
                ->where('id', $return->product_id)
                ->where('tenant_id', $tenantId)
                ->increment('available_stock', 1);

            // Clear cache
            Cache::tags(["fashion_returns:{$tenantId}"])->flush();

            Log::info('Return approved', [
                'return_id' => $returnId,
                'product_id' => $return->product_id,
            ]);

            return true;
        });
    }

    /**
     * Reject return
     */
    public function rejectReturn(int $returnId, string $rejectionReason, int $tenantId): bool
    {
        try {
            DB::table('fashion_returns')
                ->where('id', $returnId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'requested')
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => $rejectionReason,
                    'rejected_at' => Carbon::now(),
                ]);

            // Clear cache
            Cache::tags(["fashion_returns:{$tenantId}"])->flush();

            Log::info('Return rejected', [
                'return_id' => $returnId,
                'reason' => $rejectionReason,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reject return', [
                'return_id' => $returnId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process refund
     */
    public function processRefund(int $returnId, float $refundAmount, int $tenantId): bool
    {
        return DB::transaction(function () use ($returnId, $refundAmount, $tenantId) {
            $return = DB::table('fashion_returns')
                ->where('id', $returnId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->lockForUpdate()
                ->first();

            if (!$return) {
                return false;
            }

            // Update return status
            DB::table('fashion_returns')
                ->where('id', $returnId)
                ->update([
                    'status' => 'completed',
                    'refund_amount' => $refundAmount,
                    'refunded_at' => Carbon::now(),
                ]);

            // Process refund through payment service
            // This would integrate with the payment system

            // Clear cache
            Cache::tags(["fashion_returns:{$tenantId}"])->flush();

            Log::info('Refund processed', [
                'return_id' => $returnId,
                'amount' => $refundAmount,
            ]);

            return true;
        });
    }

    /**
     * Get return statistics for a store
     */
    public function getReturnStatistics(int $storeId, int $tenantId, string $period = '30d'): array
    {
        $cacheKey = "fashion_return_stats:{$tenantId}:{$storeId}:{$period}";

        return Cache::remember($cacheKey, Carbon::now()->addHours(6), function () use ($storeId, $tenantId, $period) {
            $startDate = match($period) {
                '7d' => Carbon::now()->subDays(7),
                '30d' => Carbon::now()->subDays(30),
                '90d' => Carbon::now()->subDays(90),
                default => Carbon::now()->subDays(30),
            };

            $returns = DB::table('fashion_returns')
                ->join('fashion_orders', 'fashion_returns.order_id', '=', 'fashion_orders.id')
                ->join('fashion_products', 'fashion_returns.product_id', '=', 'fashion_products.id')
                ->where('fashion_products.fashion_store_id', $storeId)
                ->where('fashion_returns.tenant_id', $tenantId)
                ->where('fashion_returns.requested_at', '>=', $startDate)
                ->get();

            $totalReturns = $returns->count();
            $approvedReturns = $returns->where('status', 'approved')->count();
            $rejectedReturns = $returns->where('status', 'rejected')->count();
            $completedReturns = $returns->where('status', 'completed')->count();

            $totalRefundAmount = $returns->where('status', 'completed')->sum('refund_amount');

            // Get top return reasons
            $topReasons = $returns->groupBy('reason')
                ->map(fn($group) => $group->count())
                ->sortDesc()
                ->take(5)
                ->toArray();

            return [
                'total_returns' => $totalReturns,
                'approved_returns' => $approvedReturns,
                'rejected_returns' => $rejectedReturns,
                'completed_returns' => $completedReturns,
                'approval_rate' => $totalReturns > 0 ? round(($approvedReturns / $totalReturns) * 100, 2) : 0,
                'total_refund_amount' => $totalRefundAmount,
                'top_return_reasons' => $topReasons,
            ];
        });
    }

    /**
     * Get user returns
     */
    public function getUserReturns(int $userId, int $tenantId): array
    {
        return DB::table('fashion_returns')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('requested_at')
            ->get()
            ->toArray();
    }
}
