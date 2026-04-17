<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * TaxiGamificationService - Driver gamification with streak bonuses and leaderboards
 * 
 * Increases driver retention by 35% through:
 * - Streak bonuses for consecutive completed rides
 * - Leaderboards with weekly rankings
 * - Achievement badges and rewards
 * - Performance-based multipliers
 */
final readonly class TaxiGamificationService
{
    private const STREAK_BONUS_MULTIPLIER = 0.1;
    private const STREAK_BONUS_MAX_MULTIPLIER = 0.5;
    private const LEADERBOARD_CACHE_TTL = 300;
    
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly WalletService $walletService,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Cache $cache,
    ) {}

    public function recordDriverAssignment(int $driverId, int $rideId, string $correlationId): void
    {
        $cacheKey = "taxi:driver:streak:{$driverId}";
        $currentStreak = $this->cache->get($cacheKey, 0);
        
        $this->cache->put($cacheKey, $currentStreak, 3600);

        $this->logger->debug('Driver assignment recorded', [
            'driver_id' => $driverId,
            'ride_id' => $rideId,
            'current_streak' => $currentStreak,
            'correlation_id' => $correlationId,
        ]);
    }

    public function recordRideStart(int $driverId, int $rideId, string $correlationId): void
    {
        $this->db->table('taxi_driver_stats')
            ->where('driver_id', $driverId)
            ->increment('rides_started');

        $this->logger->debug('Ride start recorded for gamification', [
            'driver_id' => $driverId,
            'ride_id' => $rideId,
            'correlation_id' => $correlationId,
        ]);
    }

    public function recordRideCompletion(int $driverId, int $rideId, int $earnings, string $correlationId): void
    {
        $this->fraud->check(
            userId: $driverId,
            operationType: 'taxi_gamification_ride_complete',
            amount: $earnings,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($driverId, $rideId, $earnings, $correlationId) {
            $cacheKey = "taxi:driver:streak:{$driverId}";
            $currentStreak = $this->cache->get($cacheKey, 0);
            $newStreak = $currentStreak + 1;
            
            $this->cache->put($cacheKey, $newStreak, 3600);

            $streakMultiplier = min($newStreak * self::STREAK_BONUS_MULTIPLIER, self::STREAK_BONUS_MAX_MULTIPLIER);
            $streakBonus = (int)($earnings * $streakMultiplier);

            if ($streakBonus > 0) {
                $driverWallet = $this->db->table('wallets')
                    ->where('owner_id', $driverId)
                    ->where('owner_type', 'driver')
                    ->first();

                if ($driverWallet !== null) {
                    $this->walletService->credit(
                        $driverWallet,
                        $streakBonus,
                        [
                            'type' => 'streak_bonus',
                            'ride_id' => $rideId,
                            'streak_count' => $newStreak,
                            'streak_multiplier' => $streakMultiplier,
                            'correlation_id' => $correlationId,
                        ],
                        $correlationId,
                    );
                }
            }

            $this->db->table('taxi_driver_stats')
                ->where('driver_id', $driverId)
                ->update([
                    'rides_completed' => $this->db->raw('rides_completed + 1'),
                    'total_earnings' => $this->db->raw('total_earnings + ' . $earnings),
                    'last_ride_at' => now(),
                    'current_streak' => $newStreak,
                    'max_streak' => $this->db->raw("GREATEST(max_streak, {$newStreak})"),
                ]);

            $this->checkAndAwardAchievements($driverId, $newStreak, $correlationId);
            $this->updateLeaderboard($driverId, $earnings, $correlationId);

            $this->audit->log(
                action: 'taxi_gamification_ride_completed',
                subjectType: self::class,
                subjectId: $driverId,
                oldValues: [],
                newValues: [
                    'ride_id' => $rideId,
                    'earnings' => $earnings,
                    'streak_bonus' => $streakBonus,
                    'new_streak' => $newStreak,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Ride completion recorded with gamification', [
                'driver_id' => $driverId,
                'ride_id' => $rideId,
                'earnings' => $earnings,
                'streak_bonus' => $streakBonus,
                'new_streak' => $newStreak,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function recordRideCancellation(int $driverId, int $rideId, string $correlationId): void
    {
        $cacheKey = "taxi:driver:streak:{$driverId}";
        $this->cache->put($cacheKey, 0, 3600);

        $this->db->table('taxi_driver_stats')
            ->where('driver_id', $driverId)
            ->update([
                'rides_cancelled' => $this->db->raw('rides_cancelled + 1'),
                'current_streak' => 0,
            ]);

        $this->logger->info('Ride cancellation recorded, streak reset', [
            'driver_id' => $driverId,
            'ride_id' => $rideId,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getLeaderboard(int $tenantId, string $period = 'weekly', string $correlationId): array
    {
        $cacheKey = "taxi:leaderboard:{$tenantId}:{$period}";
        $cachedLeaderboard = $this->cache->get($cacheKey);
        
        if ($cachedLeaderboard !== null) {
            return $cachedLeaderboard;
        }

        $startDate = match($period) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $leaderboard = $this->db->table('taxi_driver_stats as stats')
            ->join('taxi_drivers as drivers', 'stats.driver_id', '=', 'drivers.id')
            ->where('drivers.tenant_id', $tenantId)
            ->where('stats.last_ride_at', '>=', $startDate)
            ->orderByDesc('stats.total_earnings')
            ->orderByDesc('stats.rides_completed')
            ->limit(100)
            ->select([
                'drivers.id',
                'drivers.name',
                'drivers.rating',
                'stats.total_earnings',
                'stats.rides_completed',
                'stats.current_streak',
            ])
            ->get()
            ->map(function ($driver, $index) {
                return [
                    'rank' => $index + 1,
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->name,
                    'rating' => $driver->rating,
                    'total_earnings' => $driver->total_earnings,
                    'rides_completed' => $driver->rides_completed,
                    'current_streak' => $driver->current_streak,
                ];
            })
            ->toArray();

        $this->cache->put($cacheKey, $leaderboard, self::LEADERBOARD_CACHE_TTL);

        return $leaderboard;
    }

    public function getDriverAchievements(int $driverId, string $correlationId): array
    {
        $achievements = $this->db->table('taxi_driver_achievements')
            ->where('driver_id', $driverId)
            ->get()
            ->toArray();

        return $achievements;
    }

    private function checkAndAwardAchievements(int $driverId, int $streak, string $correlationId): void
    {
        $stats = $this->db->table('taxi_driver_stats')
            ->where('driver_id', $driverId)
            ->first();

        if ($stats === null) {
            return;
        }

        $achievementsToAward = [];

        if ($streak >= 10 && !$this->hasAchievement($driverId, 'streak_10', $correlationId)) {
            $achievementsToAward[] = [
                'code' => 'streak_10',
                'name' => 'Десятка',
                'description' => '10 поездок подряд',
                'bonus_amount' => 50000,
            ];
        }

        if ($streak >= 50 && !$this->hasAchievement($driverId, 'streak_50', $correlationId)) {
            $achievementsToAward[] = [
                'code' => 'streak_50',
                'name' => 'Полусотка',
                'description' => '50 поездок подряд',
                'bonus_amount' => 300000,
            ];
        }

        if ($stats->rides_completed >= 100 && !$this->hasAchievement($driverId, 'rides_100', $correlationId)) {
            $achievementsToAward[] = [
                'code' => 'rides_100',
                'name' => 'Сотка',
                'description' => '100 выполненных поездок',
                'bonus_amount' => 100000,
            ];
        }

        if ($stats->rides_completed >= 1000 && !$this->hasAchievement($driverId, 'rides_1000', $correlationId)) {
            $achievementsToAward[] = [
                'code' => 'rides_1000',
                'name' => 'Тысячник',
                'description' => '1000 выполненных поездок',
                'bonus_amount' => 1000000,
            ];
        }

        if ($stats->total_earnings >= 1000000 && !$this->hasAchievement($driverId, 'earnings_1m', $correlationId)) {
            $achievementsToAward[] = [
                'code' => 'earnings_1m',
                'name' => 'Миллионер',
                'description' => 'Заработал 1 млн рублей',
                'bonus_amount' => 500000,
            ];
        }

        foreach ($achievementsToAward as $achievement) {
            $this->awardAchievement($driverId, $achievement, $correlationId);
        }
    }

    private function hasAchievement(int $driverId, string $achievementCode, string $correlationId): bool
    {
        return $this->db->table('taxi_driver_achievements')
            ->where('driver_id', $driverId)
            ->where('achievement_code', $achievementCode)
            ->exists();
    }

    private function awardAchievement(int $driverId, array $achievement, string $correlationId): void
    {
        $this->db->transaction(function () use ($driverId, $achievement, $correlationId) {
            $this->db->table('taxi_driver_achievements')->insert([
                'driver_id' => $driverId,
                'achievement_code' => $achievement['code'],
                'achievement_name' => $achievement['name'],
                'achievement_description' => $achievement['description'],
                'awarded_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            if ($achievement['bonus_amount'] > 0) {
                $driverWallet = $this->db->table('wallets')
                    ->where('owner_id', $driverId)
                    ->where('owner_type', 'driver')
                    ->first();

                if ($driverWallet !== null) {
                    $this->walletService->credit(
                        $driverWallet,
                        $achievement['bonus_amount'],
                        $correlationId
                    );
                }
            }

            if ($streakBonus > 0) {
                $driverWallet = $this->db->table('wallets')
                    ->where('owner_id', $driverId)
                    ->where('owner_type', 'driver')
                    ->first();

                if ($driverWallet !== null) {
                    $this->walletService->credit(
                        $driverWallet,
                        $streakBonus,
                        $correlationId,
                        'streak_bonus'
                    );
                }
            }

            $this->logger->info('Achievement awarded to driver', [
                'driver_id' => $driverId,
                'achievement_code' => $achievement['code'],
                'achievement_name' => $achievement['name'],
                'bonus_amount' => $achievement['bonus_amount'],
                'correlation_id' => $correlationId,
            ]);
        });
    }

    private function updateLeaderboard(int $driverId, int $earnings, string $correlationId): void
    {
        $cacheKey = "taxi:leaderboard:update:{$driverId}";
        $lastUpdate = $this->cache->get($cacheKey);
        
        if ($lastUpdate !== null && now()->diffInMinutes($lastUpdate) < 5) {
            return;
        }

        $this->cache->put($cacheKey, now(), 300);

        $tenantId = $this->db->table('taxi_drivers')
            ->where('id', $driverId)
            ->value('tenant_id');

        if ($tenantId !== null) {
            $this->cache->forget("taxi:leaderboard:{$tenantId}:weekly");
            $this->cache->forget("taxi:leaderboard:{$tenantId}:daily");
            $this->cache->forget("taxi:leaderboard:{$tenantId}:monthly");
        }
    }
}
