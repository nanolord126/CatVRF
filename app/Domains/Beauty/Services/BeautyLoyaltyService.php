<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\BeautyLoyaltyDto;
use App\Domains\Beauty\Events\LoyaltyPointsEarnedEvent;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\Bonus\BonusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class BeautyLoyaltyService
{
    private const CACHE_TTL = 3600;
    private const STREAK_BONUS_MULTIPLIER = 1.5;
    private const REFERRAL_BONUS = 500;
    private const REFERRER_BONUS = 1000;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private BonusService $bonusService,
    ) {}

    public function processAction(BeautyLoyaltyDto $dto): array
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'beauty_loyalty_action',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('User-Agent'),
            correlationId: $dto->correlationId,
        );

        return DB::transaction(function () use ($dto) {
            $points = $this->calculatePoints($dto);
            $multiplier = $this->getStreakMultiplier($dto->userId);
            $finalPoints = (int) round($points * $multiplier);

            $loyaltyData = $this->getUserLoyaltyData($dto->userId);
            $loyaltyData['total_points'] += $finalPoints;
            $loyaltyData['actions_count'] += 1;
            $loyaltyData['current_streak'] = $this->updateStreak($dto->userId, $dto->action);

            if ($dto->referralCode) {
                $referralBonus = $this->processReferral($dto->referralCode, $dto->userId);
                $finalPoints += $referralBonus['referee_bonus'];
            }

            $this->saveUserLoyaltyData($dto->userId, $loyaltyData);

            $result = [
                'success' => true,
                'points_earned' => $finalPoints,
                'base_points' => $points,
                'streak_multiplier' => $multiplier,
                'total_points' => $loyaltyData['total_points'],
                'current_streak' => $loyaltyData['current_streak'],
                'tier' => $this->calculateTier($loyaltyData['total_points']),
                'referral_bonus' => $dto->referralCode ? $referralBonus : null,
                'correlation_id' => $dto->correlationId,
            ];

            Log::channel('audit')->info('Loyalty action processed', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
                'action' => $dto->action,
                'points_earned' => $finalPoints,
                'tenant_id' => $dto->tenantId,
            ]);

            event(new LoyaltyPointsEarnedEvent(
                userId: $dto->userId,
                points: $finalPoints,
                action: $dto->action,
                correlationId: $dto->correlationId,
            ));

            $this->audit->record(
                action: 'beauty_loyalty_points_earned',
                subjectType: 'BeautyLoyalty',
                subjectId: $dto->userId,
                oldValues: ['total_points' => $loyaltyData['total_points'] - $finalPoints],
                newValues: ['total_points' => $loyaltyData['total_points']],
                correlationId: $dto->correlationId,
            );

            return $result;
        });
    }

    private function calculatePoints(BeautyLoyaltyDto $dto): int
    {
        $actionPoints = [
            'appointment_completed' => 100,
            'review_left' => 50,
            'video_call_completed' => 25,
            'profile_completed' => 200,
            'first_booking' => 500,
        ];

        return $actionPoints[$dto->action] ?? 10;
    }

    private function getStreakMultiplier(int $userId): float
    {
        $streak = $this->getCurrentStreak($userId);

        if ($streak >= 7) {
            return 2.0;
        }

        if ($streak >= 3) {
            return self::STREAK_BONUS_MULTIPLIER;
        }

        return 1.0;
    }

    private function getCurrentStreak(int $userId): int
    {
        $key = "beauty:loyalty:streak:{$userId}";
        return (int) Redis::get($key) ?? 0;
    }

    private function updateStreak(int $userId, string $action): int
    {
        $key = "beauty:loyalty:streak:{$userId}";
        $lastActionKey = "beauty:loyalty:last_action:{$userId}";

        $lastActionDate = Redis::get($lastActionKey);
        $today = now()->toDateString();

        if ($lastActionDate === $today) {
            return $this->getCurrentStreak($userId);
        }

        if ($lastActionDate === now()->subDay()->toDateString()) {
            Redis::incr($key);
        } else {
            Redis::set($key, 1);
        }

        Redis::setex($lastActionKey, 86400 * 7, $today);

        return (int) Redis::get($key);
    }

    private function processReferral(string $referralCode, int $referrerId): array
    {
        $referrerId = $this->validateReferralCode($referralCode);

        if (!$referrerId) {
            return ['referrer_bonus' => 0, 'referee_bonus' => 0];
        }

        $this->bonusService->award($referrerId, self::REFERRER_BONUS, 'referral');
        $this->bonusService->award($referrerId, self::REFERRAL_BONUS, 'referral_earned');

        $this->trackReferralChain($referrerId, $referrerId);

        return [
            'referrer_bonus' => self::REFERRER_BONUS,
            'referee_bonus' => self::REFERRAL_BONUS,
        ];
    }

    private function validateReferralCode(string $code): ?int
    {
        $key = "beauty:referral:{$code}";
        $userId = Redis::get($key);

        return $userId ? (int) $userId : null;
    }

    private function trackReferralChain(int $referrerId, int $refereeId): void
    {
        $key = "beauty:referral_chain:{$referrerId}";
        Redis::sadd($key, $refereeId);
        Redis::expire($key, 86400 * 365);
    }

    private function getUserLoyaltyData(int $userId): array
    {
        $key = "beauty:loyalty:user:{$userId}";
        $data = Redis::get($key);

        if ($data) {
            return json_decode($data, true);
        }

        return [
            'total_points' => 0,
            'actions_count' => 0,
            'current_streak' => 0,
            'tier' => 'bronze',
        ];
    }

    private function saveUserLoyaltyData(int $userId, array $data): void
    {
        $key = "beauty:loyalty:user:{$userId}";
        Redis::setex($key, self::CACHE_TTL, json_encode($data));
    }

    private function calculateTier(int $totalPoints): string
    {
        if ($totalPoints >= 10000) {
            return 'platinum';
        }

        if ($totalPoints >= 5000) {
            return 'gold';
        }

        if ($totalPoints >= 2000) {
            return 'silver';
        }

        return 'bronze';
    }

    public function generateReferralCode(int $userId): string
    {
        $code = 'BEAUTY' . Str::upper(Str::random(8));
        $key = "beauty:referral:{$code}";

        Redis::setex($key, 86400 * 365, $userId);

        return $code;
    }

    public function getLoyaltyStatus(int $userId): array
    {
        $data = $this->getUserLoyaltyData($userId);
        $streak = $this->getCurrentStreak($userId);

        $referralCode = $this->getUserReferralCode($userId);
        $referralCount = $this->getReferralCount($userId);

        return [
            'total_points' => $data['total_points'],
            'current_streak' => $streak,
            'tier' => $this->calculateTier($data['total_points']),
            'referral_code' => $referralCode,
            'referrals_count' => $referralCount,
            'next_tier_points' => $this->getNextTierPoints($data['total_points']),
        ];
    }

    private function getUserReferralCode(int $userId): ?string
    {
        $pattern = "beauty:referral:*";
        $keys = Redis::keys($pattern);

        foreach ($keys as $key) {
            $storedUserId = Redis::get($key);
            if ((int) $storedUserId === $userId) {
                return str_replace('beauty:referral:', '', $key);
            }
        }

        return null;
    }

    private function getReferralCount(int $userId): int
    {
        $key = "beauty:referral_chain:{$userId}";
        return Redis::scard($key);
    }

    private function getNextTierPoints(int $currentPoints): int
    {
        if ($currentPoints < 2000) {
            return 2000;
        }

        if ($currentPoints < 5000) {
            return 5000;
        }

        if ($currentPoints < 10000) {
            return 10000;
        }

        return 0;
    }
}
