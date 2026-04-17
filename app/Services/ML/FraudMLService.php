<?php declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * Fraud ML Service
 * 
 * Machine Learning service for fraud detection and prediction.
 * Handles cancellation fraud, no-show prediction, and behavioral analysis.
 */
final readonly class FraudMLService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Connection $redis,
        private readonly DatabaseManager $db,
        private readonly Request $request
    ) {}

    /**
     * Predict cancellation fraud score.
     * 
     * Returns a score between 0 and 1, where higher values indicate higher fraud risk.
     */
    public function predictCancellationFraud(int $userId, int $bookingId, string $reason): float
    {
        $cacheKey = "fraud_ml:cancellation:{$userId}:{$bookingId}";
        $cached = Redis::get($cacheKey);
        
        if ($cached !== null) {
            return (float) $cached;
        }

        $userCancellationHistory = $this->getUserCancellationHistory($userId);
        $timeUntilDeparture = $this->getTimeUntilDeparture($bookingId);
        $reasonRiskScore = $this->getReasonRiskScore($reason);

        $fraudScore = $this->calculateFraudScore(
            userCancellationHistory: $userCancellationHistory,
            timeUntilDeparture: $timeUntilDeparture,
            reasonRiskScore: $reasonRiskScore,
        );

        Redis::setex($cacheKey, 3600, (string) $fraudScore);

        $this->logger->info('Fraud ML prediction completed', [
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'fraud_score' => $fraudScore,
            'correlation_id' => request()?->header('X-Correlation-ID'),
        ]);

        return $fraudScore;
    }

    /**
     * Predict no-show probability.
     */
    public function predictNoShowProbability(int $userId, int $bookingId): float
    {
        $cacheKey = "fraud_ml:noshow:{$userId}:{$bookingId}";
        $cached = Redis::get($cacheKey);
        
        if ($cached !== null) {
            return (float) $cached;
        }

        $userNoShowHistory = $this->getUserNoShowHistory($userId);
        $bookingValue = $this->getBookingValue($bookingId);
        $userLoyalty = $this->getUserLoyaltyScore($userId);

        $noShowScore = $this->calculateNoShowScore(
            userNoShowHistory: $userNoShowHistory,
            bookingValue: $bookingValue,
            userLoyalty: $userLoyalty,
        );

        $this->redis->setex($cacheKey, 3600, (string) $noShowScore);

        return $noShowScore;
    }

    /**
     * Get user cancellation history.
     */
    private function getUserCancellationHistory(int $userId): array
    {
        $cancellations = $this->db->table('travel_bookings')
            ->where('user_id', $userId)
            ->where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->subDays(90))
            ->count();

        $totalBookings = $this->db->table('travel_bookings')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(90))
            ->count();

        return [
            'cancellations' => $cancellations,
            'total_bookings' => $totalBookings,
            'cancellation_rate' => $totalBookings > 0 ? $cancellations / $totalBookings : 0,
        ];
    }

    /**
     * Get time until departure in hours.
     */
    private function getTimeUntilDeparture(int $bookingId): int
    {
        $booking = $this->db->table('travel_bookings')
            ->where('id', $bookingId)
            ->first();

        if (!$booking) {
            return 0;
        }

        return now()->diffInHours(\Carbon\Carbon::parse($booking->start_date));
    }

    /**
     * Get risk score based on cancellation reason.
     */
    private function getReasonRiskScore(string $reason): float
    {
        $highRiskReasons = ['duplicate booking', 'found cheaper', 'mistake', 'test'];
        $mediumRiskReasons = ['schedule conflict', 'changed plans', 'personal reasons'];
        $lowRiskReasons = ['emergency', 'health', 'family emergency'];

        $reasonLower = strtolower($reason);

        foreach ($highRiskReasons as $highRisk) {
            if (str_contains($reasonLower, $highRisk)) {
                return 0.8;
            }
        }

        foreach ($mediumRiskReasons as $mediumRisk) {
            if (str_contains($reasonLower, $mediumRisk)) {
                return 0.5;
            }
        }

        foreach ($lowRiskReasons as $lowRisk) {
            if (str_contains($reasonLower, $lowRisk)) {
                return 0.1;
            }
        }

        return 0.3;
    }

    /**
     * Calculate fraud score based on multiple factors.
     */
    private function calculateFraudScore(array $userCancellationHistory, int $timeUntilDeparture, float $reasonRiskScore): float
    {
        $cancellationRate = $userCancellationHistory['cancellation_rate'];
        
        $historyScore = min($cancellationRate * 2, 1.0);
        
        $timeScore = 0;
        if ($timeUntilDeparture < 24) {
            $timeScore = 0.9;
        } elseif ($timeUntilDeparture < 72) {
            $timeScore = 0.7;
        } elseif ($timeUntilDeparture < 168) {
            $timeScore = 0.5;
        } else {
            $timeScore = 0.2;
        }

        $fraudScore = ($historyScore * 0.4) + ($timeScore * 0.3) + ($reasonRiskScore * 0.3);

        return min(max($fraudScore, 0), 1);
    }

    /**
     * Get user no-show history.
     */
    private function getUserNoShowHistory(int $userId): array
    {
        $noShows = $this->db->table('travel_bookings')
            ->where('user_id', $userId)
            ->where('status', 'no_show')
            ->where('start_date', '>=', now()->subDays(90))
            ->count();

        $totalBookings = $this->db->table('travel_bookings')
            ->where('user_id', $userId)
            ->where('start_date', '>=', now()->subDays(90))
            ->count();

        return [
            'no_shows' => $noShows,
            'total_bookings' => $totalBookings,
            'no_show_rate' => $totalBookings > 0 ? $noShows / $totalBookings : 0,
        ];
    }

    /**
     * Get booking value.
     */
    private function getBookingValue(int $bookingId): float
    {
        $booking = $this->db->table('travel_bookings')
            ->where('id', $bookingId)
            ->first();

        return (float) ($booking->total_amount ?? 0);
    }

    /**
     * Get user loyalty score.
     */
    private function getUserLoyaltyScore(int $userId): float
    {
        $totalSpent = $this->db->table('balance_transactions')
            ->where('user_id', $userId)
            ->where('type', 'deposit')
            ->where('created_at', '>=', now()->subDays(365))
            ->sum('amount');

        if ($totalSpent > 500000) {
            return 1.0;
        } elseif ($totalSpent > 200000) {
            return 0.8;
        } elseif ($totalSpent > 50000) {
            return 0.5;
        }

        return 0.2;
    }

    /**
     * Calculate no-show score.
     */
    private function calculateNoShowScore(array $userNoShowHistory, float $bookingValue, float $userLoyalty): float
    {
        $noShowRate = $userNoShowHistory['no_show_rate'];
        
        $historyScore = min($noShowRate * 3, 1.0);
        
        $valueScore = $bookingValue < 10000 ? 0.8 : 0.3;
        
        $loyaltyScore = 1 - $userLoyalty;

        $noShowScore = ($historyScore * 0.5) + ($valueScore * 0.3) + ($loyaltyScore * 0.2);

        return min(max($noShowScore, 0), 1);
    }
}
