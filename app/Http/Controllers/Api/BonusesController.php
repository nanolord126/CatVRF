<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Bonuses\DTOs\AwardBonusDto;
use App\Domains\Bonuses\DTOs\SpendBonusDto;
use App\Domains\Bonuses\DTOs\WithdrawBonusDto;
use App\Domains\Bonuses\DTOs\BonusBalanceDto;
use App\Domains\Bonuses\Enums\BonusType;
use App\Domains\Bonuses\Facades\Bonus;
use App\Domains\Bonuses\Jobs\RecalculateBonusBalanceJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * BonusesController - API controller for bonus operations
 * 
 * Provides REST API endpoints for bonus domain operations.
 */
final readonly class BonusesController
{
    public function getBalance(string $userId): JsonResponse
    {
        $balance = Bonus::getBalance($userId);

        return response()->json([
            'success' => true,
            'data' => $balance->toArray(),
        ]);
    }

    public function getHistory(string $userId, Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $history = Bonus::getHistory($userId, $perPage);

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'pagination' => [
                'total' => $history->total(),
                'per_page' => $history->perPage(),
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
            ],
        ]);
    }

    public function calculateBonus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_amount' => 'required|numeric|min:0',
            'rule_type' => 'required|string',
            'vertical_code' => 'nullable|string',
            'user_type' => 'nullable|string|in:b2b,b2c',
        ]);

        $bonus = Bonus::calculateBonus(
            orderAmount: (float) $validated['order_amount'],
            ruleType: $validated['rule_type'],
            verticalCode: $validated['vertical_code'] ?? null,
            userType: $validated['user_type'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'bonus_amount' => $bonus,
                'bonus_amount_rub' => $bonus / 100,
            ],
        ]);
    }

    public function getRules(string $ruleType, Request $request): JsonResponse
    {
        $verticalCode = $request->input('vertical_code');
        $rules = Bonus::getRulesForVertical($ruleType, $verticalCode);

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    public function award(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'tenant_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
            'type' => 'required|string|in:loyalty,referral,first_order,promo,ai_constructor',
            'reason' => 'nullable|string',
            'source_type' => 'nullable|string',
            'source_id' => 'nullable|integer',
            'vertical_code' => 'nullable|string',
        ]);

        $transaction = Bonus::award(
            userId: $validated['user_id'],
            tenantId: $validated['tenant_id'],
            amount: $validated['amount'],
            type: $validated['type'],
            reason: $validated['reason'] ?? null,
            sourceType: $validated['source_type'] ?? null,
            sourceId: $validated['source_id'] ?? null,
            verticalCode: $validated['vertical_code'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'status' => $transaction->status,
            ],
        ]);
    }

    public function spend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'tenant_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string',
            'source_type' => 'nullable|string',
            'source_id' => 'nullable|integer',
        ]);

        Bonus::spend(
            userId: $validated['user_id'],
            tenantId: $validated['tenant_id'],
            amount: $validated['amount'],
            reason: $validated['reason'],
            sourceType: $validated['source_type'] ?? null,
            sourceId: $validated['source_id'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Bonus spent successfully',
        ]);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'tenant_id' => 'required|integer',
            'amount' => 'required|integer|min:100',
            'withdrawal_method' => 'required|string',
            'bank_account_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bic' => 'nullable|string',
            'inn' => 'nullable|string',
        ]);

        $transaction = Bonus::withdraw(
            userId: $validated['user_id'],
            tenantId: $validated['tenant_id'],
            amount: $validated['amount'],
            withdrawalMethod: $validated['withdrawal_method'],
            bankAccountNumber: $validated['bank_account_number'] ?? null,
            bankName: $validated['bank_name'] ?? null,
            bic: $validated['bic'] ?? null,
            inn: $validated['inn'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'status' => $transaction->status,
            ],
        ]);
    }

    public function unlockExpiredHolds(): JsonResponse
    {
        $count = Bonus::unlockExpiredHolds();

        return response()->json([
            'success' => true,
            'data' => [
                'unlocked_count' => $count,
            ],
        ]);
    }

    public function expireOldBonuses(): JsonResponse
    {
        $count = Bonus::expireOldBonuses();

        return response()->json([
            'success' => true,
            'data' => [
                'expired_count' => $count,
            ],
        ]);
    }

    public function recalculateBalance(string $walletId): JsonResponse
    {
        RecalculateBonusBalanceJob::dispatch(
            walletId: $walletId,
            correlationId: Str::uuid()->toString(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Balance recalculation job dispatched',
        ]);
    }
}
