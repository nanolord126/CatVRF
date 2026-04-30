<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Repositories;

use Modules\Supermarket\Domain\Repositories\ReturnRepositoryInterface;
use Modules\Supermarket\Application\DTOs\CreateReturnData;
use Modules\Supermarket\Infrastructure\Models\Return;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final class ReturnRepository implements ReturnRepositoryInterface
{
    public function findById(int $id): ?Return
    {
        return Return::with(['items', 'order', 'buyer', 'seller'])->find($id);
    }

    public function findByOrderId(int $orderId): ?Return
    {
        return Return::with(['items', 'order'])->where('order_id', $orderId)->first();
    }

    public function findByBuyerId(int $buyerId, int $perPage = 15): LengthAwarePaginator
    {
        return Return::with(['items', 'order'])
            ->where('buyer_id', $buyerId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findBySellerId(int $sellerId, int $perPage = 15): LengthAwarePaginator
    {
        return Return::with(['items', 'buyer'])
            ->where('seller_id', $sellerId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findPending(): \Illuminate\Database\Eloquent\Collection
    {
        return Return::with(['items', 'order', 'buyer'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function create(CreateReturnData $data): Return
    {
        return Return::create([
            'order_id' => $data->orderId,
            'buyer_id' => $data->buyerId,
            'seller_id' => $data->sellerId,
            'status' => 'pending',
            'reason_type' => $data->reasonType->value,
            'reason_comment' => $data->comment,
            'is_cold_chain' => $data->isColdChain,
            'return_method' => $data->returnMethod,
            'images' => $data->images,
        ]);
    }

    public function update(Return $return, array $data): bool
    {
        return $return->update($data);
    }

    public function delete(Return $return): bool
    {
        return $return->delete();
    }

    public function getStatsBySeller(int $sellerId, string $period = '30d'): array
    {
        $from = match ($period) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(30),
        };

        $stats = Return::where('seller_id', $sellerId)
            ->where('created_at', '>=', $from)
            ->selectRaw('
                COUNT(*) as total_returns,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_returns,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_returns,
                SUM(refund_amount) as total_refunded,
                AVG(refund_amount) as avg_refund_amount
            ')
            ->first();

        return [
            'total_returns' => $stats->total_returns ?? 0,
            'approved_returns' => $stats->approved_returns ?? 0,
            'rejected_returns' => $stats->rejected_returns ?? 0,
            'approval_rate' => $stats->total_returns > 0 
                ? round(($stats->approved_returns / $stats->total_returns) * 100, 2) 
                : 0,
            'total_refunded' => $stats->total_refunded ?? 0,
            'avg_refund_amount' => $stats->avg_refund_amount ?? 0,
        ];
    }
}
