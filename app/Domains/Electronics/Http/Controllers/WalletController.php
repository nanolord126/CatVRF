<?php declare(strict_types=1);

namespace App\Domains\Electronics\Http\Controllers;

use App\Domains\Electronics\DTOs\SplitPaymentRequestDto;
use App\Domains\Electronics\DTOs\SplitPaymentResponseDto;
use App\Domains\Electronics\Services\ElectronicsWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final readonly class WalletController
{
    public function __construct(
        private ElectronicsWalletService $walletService,
    ) {
    }

    public function processSplitPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'total_amount_kopecks' => 'required|integer|min:0',
            'payment_sources' => 'required|array|min:1',
            'payment_sources.*.source' => 'required|string|in:wallet,card,bonus,sbp',
            'payment_sources.*.amount_kopecks' => 'required|integer|min:0',
            'payment_sources.*.metadata' => 'nullable|array',
            'use_escrow' => 'required|boolean',
            'escrow_release_days' => 'nullable|integer|min:1|max:90',
            'metadata' => 'nullable|array',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        $correlationId = (string) Str::uuid();
        $idempotencyKey = $request->input('idempotency_key');

        if ($idempotencyKey) {
            $cachedResponse = $this->getSplitPaymentCache($idempotencyKey);
            if ($cachedResponse !== null) {
                return response()->json($cachedResponse);
            }
        }

        $dto = SplitPaymentRequestDto::fromRequest(
            $request->all(),
            $userId,
            $correlationId
        );

        $result = $this->walletService->processSplitPayment($dto);

        if ($idempotencyKey && $result->success) {
            $this->setSplitPaymentCache($idempotencyKey, $result->toArray());
        }

        return response()->json($result->toArray());
    }

    public function releaseEscrow(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|string|exists:electronics_escrow_holds,payment_id',
            'reason' => 'required|string|max:500',
        ]);

        $correlationId = (string) Str::uuid();
        $paymentId = $request->input('payment_id');
        $reason = $request->input('reason');

        try {
            $success = $this->walletService->releaseEscrow($paymentId, $reason, $correlationId);

            return response()->json([
                'success' => $success,
                'correlation_id' => $correlationId,
                'payment_id' => $paymentId,
                'reason' => $reason,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 400);
        }
    }

    public function getWalletBalance(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant()->id;

        $wallet = \Illuminate\Support\Facades\DB::table('wallets')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$wallet) {
            return response()->json([
                'balance_kopecks' => 0,
                'hold_amount_kopecks' => 0,
                'available_kopecks' => 0,
            ]);
        }

        $bonusBalance = \Illuminate\Support\Facades\DB::table('balance_transactions')
            ->where('wallet_id', $wallet->id)
            ->where('type', 'bonus')
            ->sum('amount');

        return response()->json([
            'balance_kopecks' => (int) $wallet->current_balance,
            'hold_amount_kopecks' => (int) $wallet->hold_amount,
            'available_kopecks' => (int) ($wallet->current_balance - $wallet->hold_amount),
            'bonus_balance_kopecks' => (int) $bonusBalance,
        ]);
    }

    public function getPaymentHistory(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant()->id;

        $payments = \Illuminate\Support\Facades\DB::table('electronics_split_payments')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($payments);
    }

    public function getEscrowHolds(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant()->id;

        $holds = \Illuminate\Support\Facades\DB::table('electronics_escrow_holds')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($holds);
    }

    private function getSplitPaymentCache(string $key): ?array
    {
        return \Illuminate\Support\Facades\Cache::get("split_payment:{$key}");
    }

    private function setSplitPaymentCache(string $key, array $data): void
    {
        \Illuminate\Support\Facades\Cache::put(
            "split_payment:{$key}",
            $data,
            now()->addHours(24)
        );
    }
}
