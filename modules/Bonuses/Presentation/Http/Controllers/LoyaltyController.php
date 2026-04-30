<?php

declare(strict_types=1);

namespace Modules\Bonuses\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Bonuses\Application\Services\BonusesFacadeService;
use Modules\Bonuses\Domain\ValueObjects\LoyaltyStatus;

/**
 * Controller LoyaltyController
 *
 * Handles HTTP requests for loyalty operations.
 * Provides endpoints for loyalty status, points, benefits, and tier information.
 * Integrates with BonusesFacadeService for business logic.
 */
final class LoyaltyController extends Controller
{
    public function __construct(
        private readonly BonusesFacadeService $bonusesFacade
    ) {}

    /**
     * Get the current user's loyalty status.
     */
    public function getStatus(): JsonResponse
    {
        $userId = Auth::id();
        $status = $this->bonusesFacade->getLoyaltyStatus($userId);

        return response()->json([
            'tier' => $status->tier->value,
            'points' => $status->points,
            'points_earned_this_month' => $status->pointsEarnedThisMonth,
            'discount_percentage' => $status->discountPercentage,
            'total_bonuses_earned' => $status->totalBonusesEarned,
            'total_bonuses_consumed' => $status->totalBonusesConsumed,
        ]);
    }

    /**
     * Add loyalty points to the current user.
     */
    public function addPoints(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'transaction_amount' => ['required', 'numeric', 'min:0'],
            'activity_type' => ['required', 'string'],
            'vertical' => ['nullable', 'string'],
        ]);

        $userId = Auth::id();
        $status = $this->bonusesFacade->addLoyaltyPoints(
            ownerId: $userId,
            transactionAmount: (float) $request->input('transaction_amount'),
            activityType: $request->input('activity_type'),
            context: $request->only(['vertical'])
        );

        return response()->json([
            'tier' => $status->tier->value,
            'points' => $status->points,
            'points_earned_this_month' => $status->pointsEarnedThisMonth,
        ]);
    }

    /**
     * Get loyalty tier trajectory projection.
     */
    public function getTrajectory(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'monthly_point_rate' => ['required', 'numeric', 'min:0'],
        ]);

        $userId = Auth::id();
        $status = $this->bonusesFacade->getLoyaltyStatus($userId);
        $trajectory = $this->bonusesFacade->getLoyaltyCalculator()->calculateTierTrajectory(
            ownerId: $userId,
            status: $status,
            monthlyPointRate: (float) $request->input('monthly_point_rate')
        );

        return response()->json($trajectory);
    }

    /**
     * Get available loyalty benefits.
     */
    public function getBenefits(): JsonResponse
    {
        $userId = Auth::id();
        $status = $this->bonusesFacade->getLoyaltyStatus($userId);
        $benefits = $this->bonusesFacade->getLoyaltyCalculator()->getBenefitsSummary(
            ownerId: $userId,
            status: $status
        );

        return response()->json($benefits);
    }

    /**
     * Get points needed to reach the next tier.
     */
    public function getPointsToNext(): JsonResponse
    {
        $userId = Auth::id();
        $status = $this->bonusesFacade->getLoyaltyStatus($userId);
        $pointsToNext = $this->bonusesFacade->getLoyaltyCalculator()->calculatePointsToNextTier($status);

        return response()->json([
            'current_tier' => $status->tier->value,
            'current_points' => $status->points,
            'points_to_next_tier' => $pointsToNext,
            'next_tier' => $status->tier->getNextTier()?->value ?? null,
        ]);
    }
}
