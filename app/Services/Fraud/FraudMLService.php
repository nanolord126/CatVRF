<?php declare(strict_types=1);

namespace App\Services\Fraud;

use App\Models\User;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FraudMLService
{
    /**
     * Score a user operation for fraud risk (0 = safe, 1 = certain fraud)
     * 
     * Base implementation with rule-based logic
     * Full ML version comes later with model training
     */
    public function scoreOperation(
        int $userId,
        string $operationType,
        int $amount,
        string $ipAddress,
        ?string $deviceFingerprint = null,
        array $context = []
    ): array {
        $correlationId = Str::uuid()->toString();

        try {
            // Get or create user fraud profile
            $profile = $this->getUserFraudProfile($userId);

            // Extract features
            $features = $this->extractFeatures($userId, $operationType, $amount, $ipAddress, $deviceFingerprint, $context);

            // Calculate rule-based score
            $score = $this->calculateScore($features);

            // Determine decision
            $threshold = config('fraud.threshold_by_type.' . $operationType, 0.7);
            $decision = $score > $threshold ? 'block' : 'allow';

            // Log for audit
            Log::channel('audit')->info('Fraud ML: operation scored', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'operation_type' => $operationType,
                'score' => $score,
                'threshold' => $threshold,
                'decision' => $decision,
                'features' => $features,
            ]);

            // Store for ML training later
            DB::table('fraud_attempts')->insert([
                'user_id' => $userId,
                'operation_type' => $operationType,
                'ip_address' => $ipAddress,
                'device_fingerprint' => $deviceFingerprint,
                'ml_score' => $score,
                'ml_version' => 'v1-rules',
                'decision' => $decision,
                'features_json' => json_encode($features),
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            return [
                'score' => $score,
                'decision' => $decision,
                'threshold' => $threshold,
                'features' => $features,
                'correlation_id' => $correlationId,
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Fraud ML: scoring error', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Fallback to strict mode on error
            return [
                'score' => 0.5,
                'decision' => 'review',
                'threshold' => 0.7,
                'features' => [],
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ];
        }
    }

    /**
     * Calculate fraud score from features (0-1)
     */
    private function calculateScore(array $features): float
    {
        $score = 0.0;

        // High transaction count in short period = suspicious
        if ($features['transactions_5min'] >= 5) {
            $score += 0.35;
        } elseif ($features['transactions_5min'] >= 3) {
            $score += 0.15;
        }

        // High transaction count in 1 hour
        if ($features['transactions_1hour'] >= 10) {
            $score += 0.25;
        }

        // Very large amount — suspicious regardless of account age
        if ($features['amount'] >= 1_000_000) {
            $score += 0.40;
        } elseif ($features['amount'] >= 500_000) {
            $score += 0.25;
        }

        // Large amount from new user/device
        if ($features['account_age_days'] < 30 && $features['amount'] > 100000) {
            $score += 0.30;
        }

        // Device change + high amount
        if ($features['device_changed_24h'] && $features['amount'] > 50000) {
            $score += 0.20;
        }

        // IP change + multiple transactions
        if ($features['ip_changed_24h'] && $features['transactions_1hour'] >= 3) {
            $score += 0.15;
        }

        // Geo anomaly (distance > 1000km since last transaction)
        if ($features['geo_distance_km'] > 1000 && $features['geo_distance_km'] < 99999) {
            $score += 0.20;
        }

        // Odd time of day (3am-5am)
        $hour = (int) date('H');
        if ($hour >= 3 && $hour <= 5) {
            $score += 0.05;
        }

        // Multiple failed attempts
        if ($features['failed_attempts_1hour'] >= 3) {
            $score += 0.40;
        }

        // Card binding = lower risk (trusted action)
        if (isset($features['operation_type']) && $features['operation_type'] === 'card_bind') {
            $score *= 0.7;
        }

        // Cap score at 1.0
        return min(1.0, max(0.0, $score));
    }

    /**
     * Extract features for fraud scoring
     */
    private function extractFeatures(
        int $userId,
        string $operationType,
        int $amount,
        string $ipAddress,
        ?string $deviceFingerprint,
        array $context
    ): array {
        $user = User::find($userId);
        $now = now();

        // Transaction counts — use context override if provided (for testing / real-time tracking)
        $transactions5min = isset($context['ops_in_5min'])
            ? (int) $context['ops_in_5min']
            : PaymentTransaction::where('user_id', $userId)
                ->where('created_at', '>=', $now->subMinutes(5))
                ->count();

        $transactions1hour = isset($context['ops_in_1hour'])
            ? (int) $context['ops_in_1hour']
            : PaymentTransaction::where('user_id', $userId)
                ->where('created_at', '>=', $now->subHour())
                ->count();

        // Failed attempts — use context override if provided
        $failedAttempts = isset($context['failed_attempts'])
            ? (int) $context['failed_attempts']
            : PaymentTransaction::where('user_id', $userId)
                ->where('status', 'failed')
                ->where('created_at', '>=', $now->subHour())
                ->count();

        // Last transaction for IP/device comparison
        $lastPayment = PaymentTransaction::where('user_id', $userId)
            ->latest()
            ->first();

        $ipChanged = $lastPayment && $lastPayment->ip_address !== $ipAddress;
        $deviceChanged = $lastPayment && $lastPayment->device_fingerprint !== $deviceFingerprint;

        $lastPaymentIp = $lastPayment?->ip_address;
        $lastPaymentDevice = $lastPayment?->device_fingerprint;

        // Geo distance (if we have location data)
        $geoDist = 0;
        if ($lastPaymentIp && $lastPaymentIp !== $ipAddress) {
            $geoDist = 99999; // Unknown distance
        }

        // Account age
        $accountAge = $user ? $user->created_at->diffInDays(now()) : 999;

        return [
            'user_id' => $userId,
            'operation_type' => $operationType,
            'amount' => $amount,
            'ip_address' => $ipAddress,
            'device_fingerprint' => $deviceFingerprint,
            'transactions_5min' => $transactions5min,
            'transactions_1hour' => $transactions1hour,
            'failed_attempts_1hour' => $failedAttempts,
            'ip_changed_24h' => $ipChanged,
            'device_changed_24h' => $deviceChanged,
            'geo_distance_km' => $geoDist,
            'account_age_days' => $accountAge,
            'last_payment_ip' => $lastPaymentIp,
            'last_payment_device' => $lastPaymentDevice,
            'context' => $context,
        ];
    }

    /**
     * Get or create user fraud profile
     */
    private function getUserFraudProfile(int $userId): array
    {
        $cacheKey = "fraud:profile:user:$userId";

        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            try {
                $attempts = DB::table('fraud_attempts')
                    ->where('user_id', $userId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->get();

                return [
                    'user_id' => $userId,
                    'total_attempts' => $attempts->count(),
                    'blocked_count' => $attempts->where('decision', 'block')->count(),
                    'block_rate' => $attempts->count() > 0
                        ? $attempts->where('decision', 'block')->count() / $attempts->count()
                        : 0,
                    'avg_score' => $attempts->count() > 0
                        ? $attempts->avg('ml_score')
                        : 0,
                ];
            } catch (\Throwable) {
                return ['user_id' => $userId, 'total_attempts' => 0, 'blocked_count' => 0, 'block_rate' => 0, 'avg_score' => 0];
            }
        });
    }

    /**
     * Check if operation should be blocked (high risk)
     */
    public function shouldBlock(float $score, string $operationType): bool
    {
        $threshold = config('fraud.threshold_by_type.' . $operationType, 0.7);
        return $score > $threshold;
    }

    /**
     * Report fraud attempt (manual)
     */
    public function reportFraud(int $userId, string $operationType, string $reason): void
    {
        $correlationId = Str::uuid()->toString();

        DB::table('fraud_attempts')->insert([
            'user_id' => $userId,
            'operation_type' => $operationType,
            'ml_score' => 1.0, // Manual report = definite fraud
            'decision' => 'block',
            'reason' => $reason,
            'correlation_id' => $correlationId,
            'created_at' => now(),
        ]);

        Log::channel('audit')->warning('Fraud: manually reported', [
            'user_id' => $userId,
            'operation_type' => $operationType,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);

        // Invalidate cache
        Cache::forget("fraud:profile:user:$userId");
    }
}
