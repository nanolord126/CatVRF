<?php

declare(strict_types=1);

namespace App\Domains\Sports\Services;

use App\Domains\Sports\Events\FraudDetectedEvent;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\ML\FraudMLService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

final class SportsFraudDetectionService
{
    private const CACHE_TTL = 1800;
    private const HIGH_RISK_THRESHOLD = 0.75;
    private const MEDIUM_RISK_THRESHOLD = 0.50;
    private const NO_SHOW_PENALTY_THRESHOLD = 3;
    private const CANCELLATION_RATE_THRESHOLD = 0.30;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private FraudMLService $fraudML,
        private DatabaseManager $db,
        private Cache $cache,
        private LoggerInterface $logger,
        private RedisConnection $redis,
        private Guard $guard,
    ) {}

    public function detectCancellationFraud(int $userId, int $bookingId, string $cancellationReason, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'fraud_detection_cancellation',
            amount: 0,
            correlationId: $correlationId,
        );

        $userHistory = $this->getUserCancellationHistory($userId);
        $bookingData = $this->getBookingData($bookingId);
        
        $riskScore = $this->calculateCancellationRiskScore($userHistory, $bookingData, $cancellationReason);
        
        $fraudDetails = [
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'cancellation_reason' => $cancellationReason,
            'total_bookings' => $userHistory['total_bookings'],
            'cancellations' => $userHistory['cancellations'],
            'cancellation_rate' => $userHistory['cancellation_rate'],
            'last_cancellation' => $userHistory['last_cancellation'],
            'hours_before_booking' => $bookingData['hours_before_booking'],
            'risk_score' => $riskScore,
        ];

        if ($riskScore >= self::HIGH_RISK_THRESHOLD) {
            $this->handleHighRiskFraud($userId, 'cancellation_fraud', $riskScore, $fraudDetails, $correlationId);
        } elseif ($riskScore >= self::MEDIUM_RISK_THRESHOLD) {
            $this->handleMediumRiskFraud($userId, 'cancellation_fraud', $riskScore, $fraudDetails, $correlationId);
        }

        $this->logFraudDetection('cancellation_fraud', $userId, $riskScore, $fraudDetails, $correlationId);

        return [
            'risk_score' => $riskScore,
            'risk_level' => $this->getRiskLevel($riskScore),
            'is_fraudulent' => $riskScore >= self::HIGH_RISK_THRESHOLD,
            'requires_review' => $riskScore >= self::MEDIUM_RISK_THRESHOLD,
            'fraud_details' => $fraudDetails,
        ];
    }

    public function detectNoShowFraud(int $userId, int $bookingId, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'fraud_detection_noshow',
            amount: 0,
            correlationId: $correlationId,
        );

        $userHistory = $this->getUserNoShowHistory($userId);
        $bookingData = $this->getBookingData($bookingId);
        
        $riskScore = $this->calculateNoShowRiskScore($userHistory, $bookingData);
        
        $fraudDetails = [
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'total_bookings' => $userHistory['total_bookings'],
            'no_shows' => $userHistory['no_shows'],
            'no_show_rate' => $userHistory['no_show_rate'],
            'consecutive_no_shows' => $userHistory['consecutive_no_shows'],
            'last_no_show' => $userHistory['last_no_show'],
            'hours_before_booking' => $bookingData['hours_before_booking'],
            'risk_score' => $riskScore,
        ];

        if ($riskScore >= self::HIGH_RISK_THRESHOLD) {
            $this->handleHighRiskFraud($userId, 'no_show_fraud', $riskScore, $fraudDetails, $correlationId);
        } elseif ($riskScore >= self::MEDIUM_RISK_THRESHOLD) {
            $this->handleMediumRiskFraud($userId, 'no_show_fraud', $riskScore, $fraudDetails, $correlationId);
        }

        $this->logFraudDetection('no_show_fraud', $userId, $riskScore, $fraudDetails, $correlationId);

        return [
            'risk_score' => $riskScore,
            'risk_level' => $this->getRiskLevel($riskScore),
            'is_fraudulent' => $riskScore >= self::HIGH_RISK_THRESHOLD,
            'requires_review' => $riskScore >= self::MEDIUM_RISK_THRESHOLD,
            'fraud_details' => $fraudDetails,
        ];
    }

    public function detectBookingPatternFraud(int $userId, array $bookingPattern, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'fraud_detection_pattern',
            amount: 0,
            correlationId: $correlationId,
        );

        $mlResult = $this->fraudML->scoreOperation(
            userId: $userId,
            operationType: 'booking_pattern',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: null,
            context: [
                'pattern_type' => 'booking_pattern',
                'booking_frequency' => $bookingPattern['frequency'] ?? 0,
                'booking_intervals' => $bookingPattern['intervals'] ?? [],
                'venue_switching' => $bookingPattern['venue_switching'] ?? false,
                'trainer_switching' => $bookingPattern['trainer_switching'] ?? false,
                'time_preferences' => $bookingPattern['time_preferences'] ?? [],
            ],
            correlationId: $correlationId,
        );
        
        $riskScore = $mlResult['score'] ?? 0.0;

        $fraudDetails = [
            'user_id' => $userId,
            'pattern_type' => 'booking_pattern',
            'booking_frequency' => $bookingPattern['frequency'] ?? 0,
            'venue_switching' => $bookingPattern['venue_switching'] ?? false,
            'trainer_switching' => $bookingPattern['trainer_switching'] ?? false,
            'risk_score' => $riskScore,
        ];

        if ($riskScore >= self::HIGH_RISK_THRESHOLD) {
            $this->handleHighRiskFraud($userId, 'booking_pattern_fraud', $riskScore, $fraudDetails, $correlationId);
        }

        $this->logFraudDetection('booking_pattern_fraud', $userId, $riskScore, $fraudDetails, $correlationId);

        return [
            'risk_score' => $riskScore,
            'risk_level' => $this->getRiskLevel($riskScore),
            'is_fraudulent' => $riskScore >= self::HIGH_RISK_THRESHOLD,
            'requires_review' => $riskScore >= self::MEDIUM_RISK_THRESHOLD,
            'fraud_details' => $fraudDetails,
        ];
    }

    public function applyFraudPenalty(int $userId, string $fraudType, float $riskScore, string $correlationId): void
    {
        $this->db->transaction(function () use ($userId, $fraudType, $riskScore, $correlationId) {
            $penaltyKey = "sports:fraud:penalty:{$userId}";
            
            $currentPenalties = json_decode($this->redis->get($penaltyKey) ?? '{}', true);
            $currentPenalties[$fraudType] = [
                'risk_score' => $riskScore,
                'applied_at' => now()->toIso8601String(),
                'count' => ($currentPenalties[$fraudType]['count'] ?? 0) + 1,
            ];

            $this->redis->setex($penaltyKey, 2592000, json_encode($currentPenalties));

            $this->db->table('sports_fraud_penalties')->insert([
                'user_id' => $userId,
                'fraud_type' => $fraudType,
                'risk_score' => $riskScore,
                'penalty_type' => $this->determinePenaltyType($riskScore),
                'penalty_details' => json_encode($currentPenalties),
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->audit->record(
                'fraud_penalty_applied',
                'sports_fraud_penalty',
                $userId,
                [],
                [
                    'fraud_type' => $fraudType,
                    'risk_score' => $riskScore,
                    'penalty_type' => $this->determinePenaltyType($riskScore),
                    'correlation_id' => $correlationId,
                ],
                $correlationId
            );

            $this->logger->warning('Fraud penalty applied', [
                'user_id' => $userId,
                'fraud_type' => $fraudType,
                'risk_score' => $riskScore,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function getUserFraudScore(int $userId, string $correlationId = ''): array
    {
        $cacheKey = "sports:fraud:score:{$userId}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $penalties = $this->db->table('sports_fraud_penalties')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $totalPenalties = $penalties->count();
        $highRiskPenalties = $penalties->where('risk_score', '>=', self::HIGH_RISK_THRESHOLD)->count();
        
        $fraudScore = $totalPenalties > 0 
            ? min(100.0, ($highRiskPenalties / $totalPenalties) * 100)
            : 0.0;

        $result = [
            'user_id' => $userId,
            'fraud_score' => $fraudScore,
            'total_penalties' => $totalPenalties,
            'high_risk_penalties' => $highRiskPenalties,
            'risk_level' => $this->getRiskLevel($fraudScore / 100),
            'is_restricted' => $fraudScore >= 75.0,
            'calculated_at' => now()->toIso8601String(),
        ];

        $this->cache->put($cacheKey, json_encode($result), self::CACHE_TTL);

        return $result;
    }

    private function getUserCancellationHistory(int $userId): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $totalBookings = $this->db->table('sports_bookings')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $cancellations = $this->db->table('sports_bookings')
            ->where('user_id', $userId)
            ->where('status', 'cancelled')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $lastCancellation = $this->db->table('sports_bookings')
            ->where('user_id', $userId)
            ->where('status', 'cancelled')
            ->orderBy('updated_at', 'desc')
            ->value('updated_at');

        $cancellationRate = $totalBookings > 0 ? $cancellations / $totalBookings : 0.0;

        return [
            'total_bookings' => $totalBookings,
            'cancellations' => $cancellations,
            'cancellation_rate' => $cancellationRate,
            'last_cancellation' => $lastCancellation,
        ];
    }

    private function getUserNoShowHistory(int $userId): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $totalBookings = $this->db->table('sports_bookings')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $noShows = $this->db->table('sports_bookings')
            ->where('user_id', $userId)
            ->where('status', 'no_show')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $lastNoShow = $this->db->table('sports_bookings')
            ->where('user_id', $userId)
            ->where('status', 'no_show')
            ->orderBy('updated_at', 'desc')
            ->value('updated_at');

        $consecutiveNoShows = $this->calculateConsecutiveNoShows($userId);

        $noShowRate = $totalBookings > 0 ? $noShows / $totalBookings : 0.0;

        return [
            'total_bookings' => $totalBookings,
            'no_shows' => $noShows,
            'no_show_rate' => $noShowRate,
            'consecutive_no_shows' => $consecutiveNoShows,
            'last_no_show' => $lastNoShow,
        ];
    }

    private function calculateConsecutiveNoShows(int $userId): int
    {
        $bookings = $this->db->table('sports_bookings')
            ->where('user_id', $userId)
            ->orderBy('slot_start', 'desc')
            ->limit(10)
            ->get(['status', 'slot_start']);

        $consecutive = 0;
        foreach ($bookings as $booking) {
            if ($booking->status === 'no_show') {
                $consecutive++;
            } else {
                break;
            }
        }

        return $consecutive;
    }

    private function getBookingData(int $bookingId): array
    {
        $booking = $this->db->table('sports_bookings')->where('id', $bookingId)->first();
        
        if ($booking === null) {
            return ['hours_before_booking' => 0];
        }

        $hoursBefore = $booking->slot_start 
            ? now()->diffInHours(\Carbon\Carbon::parse($booking->slot_start))
            : 0;

        return [
            'hours_before_booking' => $hoursBefore,
        ];
    }

    private function calculateCancellationRiskScore(array $userHistory, array $bookingData, string $reason): float
    {
        $score = 0.0;

        $score += $userHistory['cancellation_rate'] * 40.0;

        if ($userHistory['cancellations'] >= 5) {
            $score += 20.0;
        }

        if ($bookingData['hours_before_booking'] < 2) {
            $score += 25.0;
        } elseif ($bookingData['hours_before_booking'] < 24) {
            $score += 15.0;
        }

        if (str_contains(strtolower($reason), 'emergency') || str_contains(strtolower($reason), 'urgent')) {
            $score -= 10.0;
        }

        if ($userHistory['last_cancellation'] && now()->diffInDays($userHistory['last_cancellation']) < 7) {
            $score += 15.0;
        }

        return min(1.0, max(0.0, $score / 100.0));
    }

    private function calculateNoShowRiskScore(array $userHistory, array $bookingData): float
    {
        $score = 0.0;

        $score += $userHistory['no_show_rate'] * 50.0;

        $score += min($userHistory['consecutive_no_shows'] * 15.0, 30.0);

        if ($userHistory['no_shows'] >= self::NO_SHOW_PENALTY_THRESHOLD) {
            $score += 25.0;
        }

        if ($bookingData['hours_before_booking'] < 1) {
            $score += 10.0;
        }

        return min(1.0, max(0.0, $score / 100.0));
    }

    private function getRiskLevel(float $riskScore): string
    {
        return match (true) {
            $riskScore >= self::HIGH_RISK_THRESHOLD => 'high',
            $riskScore >= self::MEDIUM_RISK_THRESHOLD => 'medium',
            default => 'low',
        };
    }

    private function determinePenaltyType(float $riskScore): string
    {
        return match (true) {
            $riskScore >= 0.9 => 'permanent_ban',
            $riskScore >= 0.75 => 'temporary_ban_30_days',
            $riskScore >= 0.6 => 'booking_restriction_7_days',
            $riskScore >= 0.5 => 'warning',
            default => 'monitoring',
        };
    }

    private function handleHighRiskFraud(int $userId, string $fraudType, float $riskScore, array $fraudDetails, string $correlationId): void
    {
        event(new FraudDetectedEvent(
            userId: $userId,
            fraudType: $fraudType,
            riskScore: $riskScore,
            fraudDetails: $fraudDetails,
            correlationId: $correlationId,
        ));

        $this->applyFraudPenalty($userId, $fraudType, $riskScore, $correlationId);

        $this->logger->critical('High risk fraud detected', [
            'user_id' => $userId,
            'fraud_type' => $fraudType,
            'risk_score' => $riskScore,
            'correlation_id' => $correlationId,
        ]);
    }

    private function handleMediumRiskFraud(int $userId, string $fraudType, float $riskScore, array $fraudDetails, string $correlationId): void
    {
        $this->logger->warning('Medium risk fraud detected', [
            'user_id' rec>rd$userId,
            'fraud_type' => $fraudType,
            subj'ckscore' => $riskScore,
            subj'crlation_id' => $correlationId,
        ]);
    }

    private function logFraudDetection(string $fraudType, int $userId, float $riskScore, array $details, string $correlationId): void
    {
        $this->audit->record(
            'fraud_detection',
            'sports_fraud',
            $userId,
            [],
            array_merge($details, [
                'fraud_type' => $fraudType,
                'risk_score' => $riskScore,
                'correlation_id' => $correlationId,
            ]),
            $correlationId
        );
    }
}
