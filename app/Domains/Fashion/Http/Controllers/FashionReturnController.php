<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Models\FashionReturn;
use App\Domains\Fashion\Services\ReturnService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionReturnController
{
    public function __construct(
        private readonly ReturnService $returnService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function myReturns(): JsonResponse
    {
        try {
            $returns = FashionReturn::where('customer_id', auth()->id())->paginate(20);
            return response()->json(['success' => true, 'data' => $returns, 'correlation_id' => Str::uuid()->toString()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()->toString()], 500);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = (string) Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'fashion_return_store', 0, request()->ip(), null, $correlationId);

        try {
            $correlationId = Str::uuid()->toString();

            $return = $this->returnService->requestReturn(
                tenant('id'),
                request('order_id'),
                auth()->id(),
                request('return_amount'),
                request('reason'),
                $correlationId,
            );

            return response()->json(['success' => true, 'data' => $return, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()->toString()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $return = FashionReturn::findOrFail($id);
            return response()->json(['success' => true, 'data' => $return, 'correlation_id' => Str::uuid()->toString()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Return not found', 'correlation_id' => Str::uuid()->toString()], 404);
        }
    }

    public function update(int $id): JsonResponse
    {
        try {
            $return = FashionReturn::findOrFail($id);
            $correlationId = (string) Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'fashion_return_update', 0, request()->ip(), null, $correlationId);

            $this->db->transaction(function () use ($return, $id, $correlationId) {
                $return->update([...request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), 'correlation_id' => $correlationId]);
                $this->log->channel('audit')->info('Fashion return updated', ['return_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $return, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()->toString()], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $returns = FashionReturn::with('order', 'customer')->paginate(50);
            return response()->json(['success' => true, 'data' => $returns, 'correlation_id' => Str::uuid()->toString()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()->toString()], 500);
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $return = FashionReturn::findOrFail($id);
            $correlationId = (string) Str::uuid()->toString();

            $this->returnService->approveReturn($return, request('refund_amount'), $correlationId);

            return response()->json(['success' => true, 'data' => $return, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()->toString()], 500);
        }
    }

    public function reject(int $id): JsonResponse
    {
        try {
            $return = FashionReturn::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($return, $id, $correlationId) {
                $return->update(['status' => 'rejected', 'correlation_id' => $correlationId]);
                $this->log->channel('audit')->info('Fashion return rejected', ['return_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()->toString()], 500);
        }
    }

    public function analytics(): JsonResponse
    {
        try {
            $totalReturns = FashionReturn::count();
            $approvedReturns = FashionReturn::where('status', 'refunded')->count();
            $totalRefundAmount = FashionReturn::sum('refund_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_returns' => $totalReturns,
                    'approved' => $approvedReturns,
                    'total_refunded' => round($totalRefundAmount, 2),
                ],
                'correlation_id' => Str::uuid()->toString(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()->toString()], 500);
        }
    }
}
