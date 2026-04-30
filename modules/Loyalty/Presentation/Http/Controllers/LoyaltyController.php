<?php

declare(strict_types=1);

namespace Modules\Loyalty\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Loyalty\Application\Services\LoyaltyService;
use Modules\Loyalty\Application\DTOs\EnrollGuestDTO;
use Modules\Loyalty\Application\DTOs\CalculatePointsDTO;
use Modules\Loyalty\Application\DTOs\RedeemRewardDTO;
use Modules\Loyalty\Domain\Repositories\GuestLoyaltyProfileRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyProgramRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyRewardRepositoryInterface;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;
use Modules\Loyalty\Domain\ValueObjects\Points;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final readonly class LoyaltyController
{
    public function __construct(
        private LoyaltyService $loyaltyService,
        private GuestLoyaltyProfileRepositoryInterface $profileRepository,
        private LoyaltyProgramRepositoryInterface $programRepository,
        private LoyaltyRewardRepositoryInterface $rewardRepository,
        private LoggerInterface $logger,
    ) {}

    public function enrollGuest(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'program_id' => 'required|string',
                'guest_id' => 'required|integer',
                'user_id' => 'nullable|integer',
                'birthday' => 'nullable|date',
                'referred_by' => 'nullable|integer',
                'preferences' => 'nullable|array',
                'correlation_id' => 'nullable|string|max:100',
            ]);

            $dto = EnrollGuestDTO::fromArray($request->all());
            $profile = $this->loyaltyService->enrollGuest($dto);

            return new JsonResponse([
                'success' => true,
                'message' => 'Guest enrolled successfully',
                'profile' => [
                    'id' => $profile->getId(),
                    'loyalty_program_id' => $profile->getLoyaltyProgramId(),
                    'guest_id' => $profile->getGuestId(),
                    'available_points' => $profile->getAvailablePoints()->getValue(),
                    'earned_points' => $profile->getEarnedPoints()->getValue(),
                    'redeemed_points' => $profile->getRedeemedPoints()->getValue(),
                    'total_spend' => $profile->getTotalSpend()->getValue(),
                    'total_visits' => $profile->getTotalVisits(),
                    'current_tier_id' => $profile->getCurrentTierId(),
                    'enrolled_at' => $profile->getEnrolledAt()->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Guest enrollment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function calculatePoints(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'program_id' => 'required|string',
                'guest_id' => 'required|integer',
                'order_amount' => 'required|numeric|min:0',
                'tier_slug' => 'nullable|string|max:50',
                'is_first_visit' => 'boolean',
                'is_birthday' => 'boolean',
                'day_of_week' => 'nullable|string|max:10',
                'item_ids' => 'nullable|array',
                'item_ids.*' => 'integer',
                'source_type' => 'nullable|string|max:50',
                'source_id' => 'nullable|integer',
            ]);

            $dto = CalculatePointsDTO::fromArray($request->all());
            $result = $this->loyaltyService->calculatePoints($dto);

            return new JsonResponse([
                'success' => true,
                'calculation' => [
                    'base_points' => $result->basePoints->getValue(),
                    'bonus_points' => $result->bonusPoints->getValue(),
                    'total_points' => $result->totalPoints->getValue(),
                    'multiplier' => $result->multiplier,
                    'applied_rules' => $result->appliedRules,
                    'description' => $result->description,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Points calculation failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function processOrderLoyalty(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'guest_id' => 'required|integer',
                'program_id' => 'required|string',
                'order_amount' => 'required|numeric|min:0',
                'tier_slug' => 'nullable|string|max:50',
                'source_type' => 'nullable|string|max:50',
                'source_id' => 'nullable|integer',
            ]);

            $profile = $this->loyaltyService->processOrderLoyalty(
                guestId: (int) $request->input('guest_id'),
                programId: $request->input('program_id'),
                orderAmount: CurrencyAmount::fromFloat((float) $request->input('order_amount')),
                tierSlug: $request->input('tier_slug'),
                sourceType: $request->input('source_type'),
                sourceId: $request->input('source_id') ? (int) $request->input('source_id') : null
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Order loyalty processed successfully',
                'profile' => [
                    'id' => $profile->getId(),
                    'available_points' => $profile->getAvailablePoints()->getValue(),
                    'earned_points' => $profile->getEarnedPoints()->getValue(),
                    'total_spend' => $profile->getTotalSpend()->getValue(),
                    'total_visits' => $profile->getTotalVisits(),
                    'current_tier_id' => $profile->getCurrentTierId(),
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Order loyalty processing failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function redeemReward(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'profile_id' => 'required|string',
                'reward_id' => 'required|string',
                'points_cost' => 'required|numeric|min:0',
            ]);

            $dto = RedeemRewardDTO::fromArray($request->all());
            $profile = $this->loyaltyService->applyReward($dto);

            return new JsonResponse([
                'success' => true,
                'message' => 'Reward redeemed successfully',
                'profile' => [
                    'id' => $profile->getId(),
                    'available_points' => $profile->getAvailablePoints()->getValue(),
                    'redeemed_points' => $profile->getRedeemedPoints()->getValue(),
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Reward redemption failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function giveBirthdayBonus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'guest_id' => 'required|integer',
                'program_id' => 'required|string',
            ]);

            $profile = $this->loyaltyService->giveBirthdayBonus(
                guestId: (int) $request->input('guest_id'),
                programId: $request->input('program_id')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Birthday bonus given successfully',
                'profile' => [
                    'id' => $profile->getId(),
                    'available_points' => $profile->getAvailablePoints()->getValue(),
                    'earned_points' => $profile->getEarnedPoints()->getValue(),
                    'birthday_bonus_year' => $profile->getBirthdayBonusYear(),
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Birthday bonus failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function getProfileBalance(Request $request, int $guestId, string $programId): JsonResponse
    {
        try {
            $points = $this->loyaltyService->getProfileBalance($guestId, $programId);

            if (!$points) {
                return new JsonResponse(['error' => 'Profile not found'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'balance' => [
                    'guest_id' => $guestId,
                    'program_id' => $programId,
                    'available_points' => $points->getValue(),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Profile balance retrieval failed', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getProfile(Request $request, int $guestId, string $programId): JsonResponse
    {
        try {
            $profile = $this->profileRepository->findByGuestAndProgram($guestId, $programId);
            if (!$profile) {
                return new JsonResponse(['error' => 'Profile not found'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'profile' => [
                    'id' => $profile->getId(),
                    'loyalty_program_id' => $profile->getLoyaltyProgramId(),
                    'guest_id' => $profile->getGuestId(),
                    'user_id' => $profile->getUserId(),
                    'available_points' => $profile->getAvailablePoints()->getValue(),
                    'earned_points' => $profile->getEarnedPoints()->getValue(),
                    'redeemed_points' => $profile->getRedeemedPoints()->getValue(),
                    'total_spend' => $profile->getTotalSpend()->getValue(),
                    'total_visits' => $profile->getTotalVisits(),
                    'current_tier_id' => $profile->getCurrentTierId(),
                    'birthday' => $profile->getBirthday()?->format('Y-m-d'),
                    'referred_by' => $profile->getReferredBy(),
                    'enrolled_at' => $profile->getEnrolledAt()->format('Y-m-d H:i:s'),
                    'birthday_bonus_year' => $profile->getBirthdayBonusYear(),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Profile retrieval failed', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getRewards(Request $request, string $programId): JsonResponse
    {
        try {
            $rewards = $this->rewardRepository->findByProgramId($programId);

            return new JsonResponse([
                'success' => true,
                'rewards' => array_map(fn ($r) => [
                    'id' => $r->getId(),
                    'name' => $r->getName(),
                    'description' => $r->getDescription(),
                    'points_cost' => $r->getPointsCost()->getValue(),
                    'is_active' => $r->isActive(),
                    'redemption_count' => $r->getRedemptionCount(),
                    'available_from' => $r->getAvailableFrom()?->format('Y-m-d'),
                    'available_until' => $r->getAvailableUntil()?->format('Y-m-d'),
                ], $rewards),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Rewards retrieval failed', [
                'program_id' => $programId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }
}
