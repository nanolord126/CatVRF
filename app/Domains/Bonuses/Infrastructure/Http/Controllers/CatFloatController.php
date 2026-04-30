<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Infrastructure\Http\Controllers;

use App\Domains\Bonuses\DTOs\AwardLockedBonusDto;
use App\Domains\Bonuses\DTOs\FloatYieldDto;
use App\Domains\Bonuses\DTOs\StreakResultDto;
use App\Domains\Bonuses\DTOs\UnlockBonusDto;
use App\Domains\Bonuses\Services\BonusMarketplaceService;
use App\Domains\Bonuses\Services\BonusStreakService;
use App\Domains\Bonuses\Services\DailyActivityService;
use App\Domains\Bonuses\Services\FloatYieldService;
use App\Domains\Bonuses\Services\LockedBonusAwardService;
use App\Domains\Bonuses\Services\LockedBonusUnlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CatFloatController - HTTP controller for CatFloat Rewards API
 * 
 * Provides RESTful endpoints for locked bonus operations.
 */
final readonly class CatFloatController
{
    public function __construct(
        private readonly LockedBonusAwardService $awardService,
        private readonly LockedBonusUnlockService $unlockService,
        private readonly DailyActivityService $activityService,
        private readonly FloatYieldService $yieldService,
        private readonly BonusMarketplaceService $marketplaceService,
        private readonly BonusStreakService $streakService,
    ) {}

    public function awardBonus(Request $request): JsonResponse
    {
        $dto = AwardLockedBonusDto::fromRequest($request);

        $batch = $this->awardService->execute($dto);

        return response()->json([
            'success' => true,
            'data' => [
                'batch_id' => $batch->id,
                'user_id' => $batch->user_id,
                'original_amount' => $batch->original_amount,
                'remaining_locked' => $batch->remaining_locked,
                'daily_unlock_rate' => $batch->daily_unlock_rate,
                'vested_until' => $batch->vested_until->toDateString(),
                'source' => $batch->source,
                'correlation_id' => $batch->correlation_id,
            ],
        ], 201);
    }

    public function getLockedBalance(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');

        $locked = $this->awardService->getLockedBalance($userId, $tenantId);
        $available = $this->awardService->getAvailableBalance($userId, $tenantId);

        return response()->json([
            'success' => true,
            'data' => [
                'locked_balance' => $locked,
                'available_balance' => $available,
                'total_balance' => $locked + $available,
            ],
        ]);
    }

    public function unlockBonus(Request $request): JsonResponse
    {
        $dto = UnlockBonusDto::fromRequest($request);
        $dto = new UnlockBonusDto(
            userId: Auth::id(),
            tenantId: tenant('id'),
            batchId: $dto->batchId,
            instant: $dto->instant,
            instantUnlockPrice: $dto->instantUnlockPrice,
            correlationId: $dto->correlationId,
            metadata: $dto->metadata,
        );

        $unlockedAmount = $this->unlockService->execute($dto);

        return response()->json([
            'success' => true,
            'data' => [
                'unlocked_amount' => $unlockedAmount,
            ],
        ]);
    }

    public function getBatches(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');

        $batches = app(\App\Domains\Bonuses\Interfaces\LockedBonusRepositoryInterface::class)
            ->getAllBatchesForUser($userId, $tenantId);

        return response()->json([
            'success' => true,
            'data' => $batches->map(fn ($batch) => [
                'id' => $batch->id,
                'original_amount' => $batch->original_amount,
                'remaining_locked' => $batch->remaining_locked,
                'unlocked_amount' => $batch->getUnlockedAmount(),
                'unlock_percentage' => $batch->getUnlockPercentage(),
                'vested_until' => $batch->vested_until->toDateString(),
                'days_remaining' => $batch->getDaysRemaining(),
                'source' => $batch->source,
                'is_fully_vested' => $batch->isFullyVested(),
            ]),
        ]);
    }

    public function logActivity(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');
        $actionType = $request->input('action_type');
        $metadata = $request->input('metadata', []);

        $log = $this->activityService->processActivity($userId, $tenantId, $actionType, $metadata);

        return response()->json([
            'success' => true,
            'data' => [
                'log_id' => $log->id,
                'activity_date' => $log->activity_date->toDateString(),
                'action_count' => $log->action_count,
                'streak_days' => $log->streak_days,
            ],
        ]);
    }

    public function processStreak(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');

        $result = $this->activityService->processStreak($userId, $tenantId);

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
        ]);
    }

    public function getStreakInfo(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');

        $info = $this->streakService->getStreakInfo($userId, $tenantId);

        return response()->json([
            'success' => true,
            'data' => $info,
        ]);
    }

    public function claimYield(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');

        $claimed = $this->activityService->claimYield($userId, $tenantId);

        return response()->json([
            'success' => true,
            'data' => [
                'claimed' => $claimed,
            ],
        ]);
    }

    public function calculateYield(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');

        $transaction = $this->yieldService->calculateYieldForUser($userId, $tenantId);

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'total_float' => $transaction->total_float,
                'user_yield' => $transaction->user_yield,
                'platform_yield' => $transaction->platform_yield,
                'total_yield' => $transaction->getTotalYield(),
                'yield_rate' => $transaction->yield_rate,
                'yield_date' => $transaction->yield_date->toDateString(),
            ],
        ]);
    }

    public function getYieldHistory(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');
        $limit = (int) $request->input('limit', 30);

        $history = $this->yieldService->getUserYieldHistory($userId, $tenantId, $limit);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    public function sellLockedBonus(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');
        $batchId = (int) $request->input('batch_id');
        $discount = (float) $request->input('discount');

        $this->marketplaceService->sellLockedBonus($userId, $tenantId, $batchId, $discount);

        return response()->json([
            'success' => true,
            'message' => 'Locked bonus sold successfully',
        ]);
    }

    public function getMarketplaceInfo(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'commission_rate' => $this->marketplaceService->getCommissionRate(),
                'min_discount' => 15,
                'max_discount' => 40,
            ],
        ]);
    }

    public function getDashboard(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant('id');

        $locked = $this->awardService->getLockedBalance($userId, $tenantId);
        $available = $this->awardService->getAvailableBalance($userId, $tenantId);
        $streakInfo = $this->streakService->getStreakInfo($userId, $tenantId);
        $streakMultiplier = $this->streakService->getStreakBonusMultiplier($userId, $tenantId);

        return response()->json([
            'success' => true,
            'data' => [
                'balances' => [
                    'locked' => $locked,
                    'available' => $available,
                    'total' => $locked + $available,
                ],
                'streak' => $streakInfo,
                'multiplier' => $streakMultiplier,
            ],
        ]);
    }
}
