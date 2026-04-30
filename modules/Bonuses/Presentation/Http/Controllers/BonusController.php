<?php

declare(strict_types=1);

namespace Modules\Bonuses\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Bonuses\Application\Services\BonusesFacadeService;
use Modules\Bonuses\Domain\Enums\BonusStatus;
use Modules\Bonuses\Domain\Enums\BonusType;

/**
 * Controller BonusController
 *
 * Handles HTTP requests for bonus operations.
 * Provides endpoints for awarding, consuming, and managing bonuses.
 * Integrates with BonusesFacadeService for business logic.
 */
final class BonusController extends Controller
{
    public function __construct(
        private readonly BonusesFacadeService $bonusesFacade
    ) {}

    /**
     * Award a bonus to a user.
     */
    public function award(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'owner_id' => ['required', 'uuid'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'type' => ['required', 'string', 'in:loyalty,referral,compensation,promotional,turnover,action'],
            'source_id' => ['nullable', 'string'],
            'source_type' => ['nullable', 'string'],
            'vertical' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $bonus = $this->bonusesFacade->awardBonus(
            ownerId: $request->input('owner_id'),
            type: BonusType::fromString($request->input('type')),
            amount: (int) $request->input('amount'),
            context: $request->only(['source_id', 'source_type', 'vertical', 'metadata'])
        );

        return response()->json([
            'id' => $bonus->getId(),
            'owner_id' => $bonus->getOwnerId(),
            'amount' => $bonus->getRemainingAmount()->getAmount(),
            'type' => $bonus->getType()->value,
            'status' => $bonus->getStatus()->value,
            'expires_at' => $bonus->getExpiresAt()?->format('Y-m-d H:i:s'),
        ], 201);
    }

    /**
     * Consume bonuses.
     */
    public function consume(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'transaction_id' => ['nullable', 'string'],
            'vertical' => ['nullable', 'string'],
        ]);

        $userId = Auth::id();
        $consumedIds = $this->bonusesFacade->consumeBonus(
            ownerId: $userId,
            amount: (int) $request->input('amount'),
            context: $request->only(['transaction_id', 'vertical'])
        );

        return response()->json([
            'consumed_bonus_ids' => $consumedIds,
            'remaining_balance' => $this->bonusesFacade->getAvailableBalance($userId),
        ]);
    }

    /**
     * Get user's available balance.
     */
    public function getBalance(): JsonResponse
    {
        $userId = Auth::id();
        $balance = $this->bonusesFacade->getAvailableBalance($userId);

        return response()->json([
            'balance' => $balance,
            'user_id' => $userId,
        ]);
    }

    /**
     * Get balance breakdown by type.
     */
    public function getBalanceByType(): JsonResponse
    {
        $userId = Auth::id();
        $balanceByType = $this->bonusesFacade->getBalanceByType($userId);

        return response()->json([
            'balance_by_type' => $balanceByType,
            'user_id' => $userId,
        ]);
    }

    /**
     * Get user's bonuses.
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $userId = Auth::id();
        $status = $request->query('status') ? BonusStatus::fromString($request->query('status')) : null;

        $bonuses = $this->bonusesFacade->getBonusesForOwner($userId, $status);

        return response()->json([
            'data' => array_map(fn ($bonus) => $bonus->toArray(), $bonuses),
        ]);
    }

    /**
     * Get specific bonus details.
     */
    public function show(string $id): JsonResponse
    {
        // TODO: Implement through facade - need add findById method
        return response()->json(['message' => 'Not yet implemented'], 501);
    }

    /**
     * Freeze a bonus.
     */
    public function freeze(string $id, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string']]);

        $this->bonusesFacade->freezeBonus(
            bonusId: $id,
            reason: $request->input('reason'),
            frozenBy: Auth::id()
        );

        return response()->json(['message' => 'Bonus frozen successfully']);
    }

    /**
     * Unfreeze a bonus.
     */
    public function unfreeze(string $id): JsonResponse
    {
        $this->bonusesFacade->unfreezeBonus($id);

        return response()->json(['message' => 'Bonus unfrozen successfully']);
    }

    /**
     * Cancel a bonus.
     */
    public function cancel(string $id): JsonResponse
    {
        $this->bonusesFacade->cancelBonus($id);

        return response()->json(['message' => 'Bonus cancelled successfully']);
    }

    /**
     * Get expiring bonuses (admin).
     */
    public function getExpiring(\Illuminate\Http\Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 7);
        $bonuses = $this->bonusesFacade->getExpiringBonuses($days);

        return response()->json([
            'data' => array_map(fn ($bonus) => $bonus->toArray(), $bonuses),
        ]);
    }

    /**
     * Get frozen bonuses (admin).
     */
    public function getFrozen(): JsonResponse
    {
        // TODO: Implement through facade
        return response()->json(['message' => 'Not yet implemented'], 501);
    }

    /**
     * Search bonuses (admin).
     */
    public function search(\Illuminate\Http\Request $request): JsonResponse
    {
        // TODO: Implement through facade
        return response()->json(['message' => 'Not yet implemented'], 501);
    }

    /**
     * Get analytics (admin).
     */
    public function getAnalytics(): JsonResponse
    {
        // TODO: Implement through facade
        return response()->json(['message' => 'Not yet implemented'], 501);
    }

    /**
     * Trigger bonus expiration (admin).
     */
    public function expireBonuses(): JsonResponse
    {
        $expiredCount = $this->bonusesFacade->expireBonuses();

        return response()->json([
            'expired_count' => $expiredCount,
        ]);
    }

    /**
     * Get bonuses by correlation ID (admin).
     */
    public function getByCorrelationId(string $correlationId): JsonResponse
    {
        // TODO: Implement through facade
        return response()->json(['message' => 'Not yet implemented'], 501);
    }
}
