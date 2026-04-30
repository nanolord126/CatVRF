<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Http\Controllers;

use App\Domains\Electronics\DTOs\SplitPaymentRequestDto;
use App\Domains\Electronics\Services\ElectronicsWalletService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final readonly class WalletController
{
    public function __construct(
        private readonly ElectronicsWalletService $walletService,
        private readonly Guard $auth,
        private readonly CacheManager $cache,
        private readonly DatabaseManager $db,
    ) {}

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

        $userId = $this->auth->id();
        $correlationId = (string) Str::uuid();
        $idempotencyKey = $request->input('idempotency_key');

        if ($idempotencyKey) {
            $cachedResponse = $this->getSplitPaymentCache($idempotencyKey);
            if ($cachedResponse !== null) {
                return new JsonResponse($cachedResponse);
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

        return new JsonResponse($result->toArray());
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

            return new JsonResponse([
                'success' => $success,
                'correlation_id' => $correlationId,
                'payment_id' => $paymentId,
                'reason' => $reason,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 400);
        }
    }

    public function getWalletBalance(Request $request): JsonResponse
    {
        $userId = $this->auth->id();
        $tenantId = tenant()->id;

        $wallet = $this->db->table('wallets')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $wallet) {
            return new JsonResponse([
                'balance_kopecks' => 0,
                'hold_amount_kopecks' => 0,
                'available_kopecks' => 0,
            ]);
        }

        $bonusBalance = $this->db->table('balance_transactions')
            ->where('wallet_id', $wallet->id)
            ->where('type', 'bonus')
            ->sum('amount');

        return new JsonResponse([
            'balance_kopecks' => (int) $wallet->current_balance,
            'hold_amount_kopecks' => (int) $wallet->hold_amount,
            'available_kopecks' => (int) ($wallet->current_balance - $wallet->hold_amount),
            'bonus_balance_kopecks' => (int) $bonusBalance,
        ]);
    }

    public function getPaymentHistory(Request $request): JsonResponse
    {
        $userId = $this->auth->id();
        $tenantId = tenant()->id;

        $payments = $this->db->table('electronics_split_payments')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return new JsonResponse($payments);
    }

    public function getEscrowHolds(Request $request): JsonResponse
    {
        $userId = $this->auth->id();
        $tenantId = tenant()->id;

        $holds = $this->db->table('electronics_escrow_holds')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return new JsonResponse($holds);
    }

    private function getSplitPaymentCache(string $key): ?array
    {
        return $this->cache->get("split_payment:{$key}");
    }

    private function setSplitPaymentCache(string $key, array $data): void
    {
        $this->cache->put(
            "split_payment:{$key}",
            $data,
            CarbonImmutable::now()->addHours(24)
        );
    }
}
