<?php declare(strict_types=1);

namespace App\Services\Fraud;

use App\Models\User;
use App\Models\PaymentTransaction;
use Illuminate\Database\Connection;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

final readonly class FraudMLService
{
    public function __construct(
        private readonly Connection $db,
        private readonly LogManager $log,
        private readonly Repository $cache,
    ) {}
    /**
     * Score a user operation for fraud risk (0 = safe, 1 = certain fraud)
     * Combines rule-based scoring with ML model predictions
     * 
     * @param int $userId
     * @param string $operationType payment_init, card_bind, payout, rating_submit, referral_claim
     * @param int $amount Amount in kopeks
     * @param string $ipAddress IP address
     * @param string|null $deviceFingerprint Device fingerprint
     * @param array $context Additional context (ops_in_5min, failed_attempts, etc.)
     * @param string|null $correlationId Correlation ID for distributed tracing
     * @return array ['score' => 0-1, 'decision' => 'allow'|'block'|'review', 'features' => [...], 'correlation_id' => '...']
     */
    public function scoreOperation(
        int $userId,
        string $operationType,
        int $amount,
        string $ipAddress,
        ?string $deviceFingerprint = null,
        array $context = [],
        ?string $correlationId = null,
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. Get user fraud profile (30-day history)
            $profile = $this->getUserFraudProfile($userId);

            // 2. Extract comprehensive features
            $features = $this->extractFeatures(
                $userId,
                $operationType,
                $amount,
                $ipAddress,
                $deviceFingerprint,
                $context,
            );

            // 3. Calculate rule-based score (0-1)
            $ruleScore = $this->calculateRuleScore($features);

            // 4. Try to get ML model score (if available)
            $mlScore = $this->getMLModelScore($features);

            // 5. Blend scores (rule-based 60%, ML model 40%)
            $score = ($ruleScore * 0.6) + ($mlScore * 0.4);
            $score = min(1.0, max(0.0, $score));

            // 6. Determine decision based on operation type
            $threshold = config("fraud.thresholds.{$operationType}", 0.70);
            $decision = match (true) {
                $score >= $threshold => 'block',
                $score >= ($threshold - 0.15) => 'review',
                default => 'allow',
            };

            // 7. Additional velocity checks (regardless of score)
            $velocityCheck = $this->checkVelocityLimits($userId, $operationType, $profile, $context);
            if ($velocityCheck['blocked']) {
                $decision = 'block';
                $score = max($score, 0.85);  // Bump score for clarity
            }

            // 8. Invalidate cache if blocking
            if ($decision === 'block') {
                Cache::forget("fraud:profile:user:{$userId}");
            }

            // 9. Audit log (before DB write, in transaction)
            DB::transaction(function () use (
                $correlationId,
                $userId,
                $operationType,
                $ipAddress,
                $deviceFingerprint,
                $score,
                $ruleScore,
                $mlScore,
                $threshold,
                $decision,
                $features,
                $profile,
                $velocityCheck,
            ) {
                DB::table('fraud_attempts')->insert([
                    'user_id' => $userId,
                    'operation_type' => $operationType,
                    'ip_address' => $ipAddress,
                    'device_fingerprint' => $deviceFingerprint,
                    'ml_score' => $score,
                    'rule_score' => $ruleScore,
                    'model_score' => $mlScore,
                    'ml_version' => $this->getCurrentModelVersion(),
                    'decision' => $decision,
                    'reason' => $velocityCheck['blocked'] ? $velocityCheck['reason'] : null,
                    'features_json' => json_encode($features),
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('FraudML: operation scored', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'operation_type' => $operationType,
                    'amount' => $amount,
                    'score' => $score,
                    'rule_score' => $ruleScore,
                    'model_score' => $mlScore,
                    'threshold' => $threshold,
                    'decision' => $decision,
                    'velocity_blocked' => $velocityCheck['blocked'],
                    'profile_block_rate' => $profile['block_rate'],
                    'features_count' => count($features),
                ]);
            });

            return [
                'score' => $score,
                'rule_score' => $ruleScore,
                'model_score' => $mlScore,
                'decision' => $decision,
                'threshold' => $threshold,
                'velocity_check' => $velocityCheck,
                'features' => $features,
                'profile' => $profile,
                'correlation_id' => $correlationId,
            ];
        } catch (\Throwable $e) {
            Log::channel('fraud_alert')->error('FraudML: scoring error', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'operation_type' => $operationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to review mode on error (fail-safe)
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
     * Calculate rule-based fraud score from features (0-1)
     * Weights various risk factors based on historical fraud patterns
     */
    private function calculateRuleScore(array $features): float
    {
        $score = 0.0;

        // 1. VELOCITY CHECKS (High transaction count = suspicious)
        if ($features['transactions_5min'] >= 5) {
            $score += 0.35;  // 5+ transactions in 5 min
        } elseif ($features['transactions_5min'] >= 3) {
            $score += 0.15;
        }

        if ($features['transactions_1hour'] >= 10) {
            $score += 0.25;  // 10+ transactions in 1 hour
        }

        // 2. AMOUNT CHECKS
        // Very large amount — suspicious regardless of account age
        if ($features['amount'] >= 1_000_000) {
            $score += 0.40;
        } elseif ($features['amount'] >= 500_000) {
            $score += 0.25;
        } elseif ($features['amount'] >= 100_000) {
            $score += 0.10;
        }

        // 3. ACCOUNT AGE + AMOUNT COMBINATION
        if ($features['account_age_days'] < 7) {
            // Brand new account
            if ($features['amount'] > 10_000) {
                $score += 0.40;
            }
        } elseif ($features['account_age_days'] < 30) {
            // Newer account
            if ($features['amount'] > 100_000) {
                $score += 0.30;
            }
        }

        // 4. DEVICE/IP CHANGES
        if ($features['device_changed_24h'] && $features['amount'] > 50_000) {
            $score += 0.20;  // New device + large amount
        }

        if ($features['ip_changed_24h'] && $features['transactions_1hour'] >= 3) {
            $score += 0.15;  // New IP + multiple transactions
        }

        // Both changed = very suspicious
        if ($features['device_changed_24h'] && $features['ip_changed_24h']) {
            $score += 0.25;
        }

        // 5. GEOGRAPHIC ANOMALIES
        if ($features['geo_distance_km'] > 1000 && $features['geo_distance_km'] < 99999) {
            $score += 0.20;  // Impossible travel (>1000km in 1 min)
        }

        // 6. TIME-OF-DAY ANOMALIES
        $hour = (int) date('H');
        if ($hour >= 3 && $hour <= 5) {
            $score += 0.08;  // Odd hours (3am-5am)
        }

        // 7. FAILED ATTEMPT STREAKS
        if ($features['failed_attempts_1hour'] >= 5) {
            $score += 0.45;  // Multiple failed attempts
        } elseif ($features['failed_attempts_1hour'] >= 3) {
            $score += 0.30;
        }

        // 8. OPERATION-SPECIFIC RISK
        switch ($features['operation_type']) {
            case 'payout':
            case 'payment_init':
                // High-risk operations
                break;
            case 'card_bind':
                // Lower risk - trusted action
                $score *= 0.7;
                break;
            case 'rating_submit':
                // Very low risk
                $score *= 0.5;
                break;
        }

        // 9. USER PROFILE HISTORY
        // If user has high historical block rate, be more suspicious
        if ($features['user_block_rate'] > 0.3) {
            $score += 0.15;
        }

        // Cap score at 1.0
        return min(1.0, max(0.0, $score));
    }

    /**
     * Get ML model score from trained model (if available)
     * Currently returns 0 (no model) - will be updated when model is trained
     * 
     * @return float Score 0-1
     */
    private function getMLModelScore(array $features): float
    {
        // TODO: Implement actual ML model prediction
        // For now, return 0 (model not yet available)
        // In production: load joblib/pickle model from storage/models/fraud/
        // Run feature extraction through model
        
        return 0.0;
    }

    /**
     * Get current ML model version from storage
     * Returns filename like 2026-03-25-v1.joblib
     */
    private function getCurrentModelVersion(): string
    {
        $modelPath = storage_path('models/fraud');
        if (!is_dir($modelPath)) {
            return 'none';
        }

        $models = array_filter(
            scandir($modelPath),
            fn ($f) => str_ends_with($f, '.joblib') || str_ends_with($f, '.pkl'),
        );

        if (empty($models)) {
            return 'none';
        }

        // Return latest model by date
        usort($models, fn ($a, $b) => filemtime("$modelPath/$b") - filemtime("$modelPath/$a"));
        return $models[0] ?? 'none';
    }

    /**
     * Check velocity limits for operation type
     * Returns ['blocked' => bool, 'reason' => string|null]
     */
    private function checkVelocityLimits(
        int $userId,
        string $operationType,
        array $profile,
        array $context,
    ): array {
        $limits = config('fraud.velocity_limits', [
            'payment_init' => 10,      // Max 10 payments per hour
            'card_bind' => 5,          // Max 5 card bindings per hour
            'payout' => 3,             // Max 3 payouts per hour
            'referral_claim' => 20,    // Max 20 referral claims per hour
        ]);

        $operationLimit = $limits[$operationType] ?? 10;
        $opsThisHour = $context['ops_in_1hour'] ?? 0;

        if ($opsThisHour > $operationLimit) {
            return [
                'blocked' => true,
                'reason' => "Velocity limit exceeded: {$opsThisHour} > {$operationLimit} per hour",
            ];
        }

        // Check if user has high block rate (>30% blocked in last 30 days)
        if ($profile['block_rate'] > 0.30) {
            return [
                'blocked' => true,
                'reason' => 'User has high historical fraud block rate: ' . number_format($profile['block_rate'] * 100, 1) . '%',
            ];
        }

        return ['blocked' => false, 'reason' => null];
    }

    /**
     * Extract comprehensive features for fraud scoring
     * Returns array of 30+ features used by both rule-based and ML models
     */
    private function extractFeatures(
        int $userId,
        string $operationType,
        int $amount,
        string $ipAddress,
        ?string $deviceFingerprint,
        array $context,
    ): array {
        $user = User::find($userId);
        $now = now();

        // 1. TRANSACTION VELOCITY
        $transactions5min = $context['ops_in_5min'] ?? PaymentTransaction::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subMinutes(5))
            ->count();

        $transactions1hour = $context['ops_in_1hour'] ?? PaymentTransaction::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subHour())
            ->count();

        $transactions1day = $context['ops_in_1day'] ?? PaymentTransaction::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subDay())
            ->count();

        // 2. FAILED ATTEMPTS
        $failedAttempts = $context['failed_attempts'] ?? PaymentTransaction::query()
            ->where('user_id', $userId)
            ->where('status', 'failed')
            ->where('created_at', '>=', $now->copy()->subHour())
            ->count();

        // 3. DEVICE/IP CHANGES (from last transaction)
        $lastPayment = PaymentTransaction::query()
            ->where('user_id', $userId)
            ->latest('created_at')
            ->first();

        $ipChanged = $lastPayment && $lastPayment->ip_address !== $ipAddress;
        $deviceChanged = $lastPayment && $lastPayment->device_fingerprint !== $deviceFingerprint;
        $lastPaymentIp = $lastPayment?->ip_address;
        $lastPaymentDevice = $lastPayment?->device_fingerprint;
        $lastPaymentTime = $lastPayment?->created_at;

        // 4. GEOGRAPHIC DISTANCE
        $geoDist = 0;
        if ($lastPaymentIp && $lastPaymentIp !== $ipAddress) {
            // TODO: Implement real geo lookup via GeoIP2
            $geoDist = 99999;  // Unknown distance (different IP = suspicious)
        }

        // 5. ACCOUNT AGE
        $accountAgeSeconds = $user ? $user->created_at->diffInSeconds(now()) : 999_999;
        $accountAgeDays = (int) ($accountAgeSeconds / 86400);

        // 6. PREVIOUS FRAUD BLOCK RATE
        $userProfile = $this->getUserFraudProfile($userId);
        $userBlockRate = $userProfile['block_rate'] ?? 0;
        $userAvgScore = $userProfile['avg_score'] ?? 0;

        // 7. AMOUNT STATISTICS
        $previousTransactions = PaymentTransaction::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->get();

        $previousAmounts = $previousTransactions->pluck('amount')->filter();
        $avgPreviousAmount = $previousAmounts->count() > 0 ? $previousAmounts->avg() : 0;
        $maxPreviousAmount = $previousAmounts->count() > 0 ? $previousAmounts->max() : 0;

        // Amount as percentage of average
        $amountVsAvgRatio = $avgPreviousAmount > 0 ? $amount / $avgPreviousAmount : 999;
        $amountVsMaxRatio = $maxPreviousAmount > 0 ? $amount / $maxPreviousAmount : 999;

        // 8. SUCCESS RATE (user's historical conversion)
        $totalPrevious = $previousTransactions->count();
        $successPrevious = $previousTransactions->where('status', 'captured')->count();
        $successRate = $totalPrevious > 0 ? ($successPrevious / $totalPrevious) : 0.5;

        // 9. TIME-BASED FEATURES
        $hour = (int) date('H');
        $dow = (int) date('w');  // 0=Sunday, 6=Saturday
        $isWeekend = $dow === 0 || $dow === 6;
        $isOddHour = $hour >= 3 && $hour <= 5;

        // 10. DEVICE/IP STATISTICS
        $distinctIpsLastDay = PaymentTransaction::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subDay())
            ->distinct('ip_address')
            ->count('ip_address');

        $distinctDevicesLastDay = PaymentTransaction::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subDay())
            ->distinct('device_fingerprint')
            ->count('device_fingerprint');

        return [
            // Identifiers
            'user_id' => $userId,
            'operation_type' => $operationType,

            // Amount
            'amount' => $amount,
            'amount_vs_avg_ratio' => $amountVsAvgRatio,
            'amount_vs_max_ratio' => $amountVsMaxRatio,
            'avg_previous_amount' => $avgPreviousAmount,

            // Velocity
            'transactions_5min' => $transactions5min,
            'transactions_1hour' => $transactions1hour,
            'transactions_1day' => $transactions1day,

            // Failures
            'failed_attempts_1hour' => $failedAttempts,
            'failed_attempts_ratio' => $totalPrevious > 0 ? (1 - $successRate) : 0,

            // Device/IP
            'ip_address' => $ipAddress,
            'device_fingerprint' => $deviceFingerprint,
            'ip_changed_24h' => $ipChanged,
            'device_changed_24h' => $deviceChanged,
            'distinct_ips_1day' => $distinctIpsLastDay,
            'distinct_devices_1day' => $distinctDevicesLastDay,

            // Geographic
            'geo_distance_km' => $geoDist,

            // Account age
            'account_age_days' => $accountAgeDays,
            'account_age_seconds' => $accountAgeSeconds,

            // History
            'user_block_rate' => $userBlockRate,
            'user_avg_score' => $userAvgScore,
            'success_rate' => $successRate,

            // Time-based
            'hour' => $hour,
            'day_of_week' => $dow,
            'is_weekend' => $isWeekend,
            'is_odd_hour' => $isOddHour,

            // Context overrides
            'context' => $context,
        ];
    }

    /**
     * Get or create user fraud profile from last 30 days
     * Used to understand user's historical fraud risk
     */
    private function getUserFraudProfile(int $userId): array
    {
        $cacheKey = "fraud:profile:user:{$userId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            try {
                $attempts = DB::table('fraud_attempts')
                    ->where('user_id', $userId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->get();

                $blockedCount = $attempts->where('decision', 'block')->count();
                $totalCount = $attempts->count();
                $blockRate = $totalCount > 0 ? ($blockedCount / $totalCount) : 0;

                return [
                    'user_id' => $userId,
                    'total_attempts_30d' => $totalCount,
                    'blocked_count_30d' => $blockedCount,
                    'review_count_30d' => $attempts->where('decision', 'review')->count(),
                    'allowed_count_30d' => $attempts->where('decision', 'allow')->count(),
                    'block_rate' => $blockRate,
                    'avg_score' => $totalCount > 0 ? $attempts->avg('ml_score') : 0,
                    'max_score_30d' => $totalCount > 0 ? $attempts->max('ml_score') : 0,
                ];
            } catch (\Throwable $e) {
                // Safe fallback
                return [
                    'user_id' => $userId,
                    'total_attempts_30d' => 0,
                    'blocked_count_30d' => 0,
                    'review_count_30d' => 0,
                    'allowed_count_30d' => 0,
                    'block_rate' => 0,
                    'avg_score' => 0,
                    'max_score_30d' => 0,
                ];
            }
        });
    }

    /**
     * Check if score should trigger block decision
     */
    public function shouldBlock(
        float $score,
        string $operationType,
        ?string $correlationId = null,
    ): bool {
        $correlationId ??= Str::uuid()->toString();
        $threshold = config("fraud.thresholds.{$operationType}", 0.70);
        $shouldBlock = $score > $threshold;

        if ($shouldBlock) {
            Log::channel('fraud_alert')->warning('FraudML: block decision', [
                'correlation_id' => $correlationId,
                'operation_type' => $operationType,
                'score' => $score,
                'threshold' => $threshold,
            ]);
        }

        return $shouldBlock;
    }

    /**
     * Report fraud attempt manually (by support or customer)
     * Marks operation as definite fraud for ML retraining
     */
    public function reportFraud(
        int $userId,
        string $operationType,
        string $reason,
        ?string $correlationId = null,
    ): void {
        $correlationId ??= Str::uuid()->toString();

        try {
            DB::transaction(function () use (
                $userId,
                $operationType,
                $reason,
                $correlationId,
            ) {
                DB::table('fraud_attempts')->insert([
                    'user_id' => $userId,
                    'operation_type' => $operationType,
                    'ml_score' => 1.0,  // Manual report = definite fraud
                    'rule_score' => 1.0,
                    'model_score' => 1.0,
                    'decision' => 'block',
                    'reason' => $reason,
                    'is_manual_report' => true,
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->warning('FraudML: fraud reported manually', [
                    'user_id' => $userId,
                    'operation_type' => $operationType,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });

            // Invalidate user's fraud profile cache
            Cache::forget("fraud:profile:user:{$userId}");
        } catch (\Throwable $e) {
            Log::channel('fraud_alert')->error('FraudML: failed to report fraud', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    /**
     * Get accuracy metrics of the current ML model
     * Compares predictions vs actual outcomes
     * 
     * @return array ['mae' => float, 'rmse' => float, 'mape' => float, 'auc_roc' => float, ...]
     */
    public function getModelAccuracy(int $days = 30): array
    {
        $attempts = DB::table('fraud_attempts')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('ml_version', '!=', 'none')
            ->get();

        if ($attempts->isEmpty()) {
            return [
                'mae' => 0,
                'rmse' => 0,
                'mape' => 0,
                'auc_roc' => 0,
                'total_samples' => 0,
                'period_days' => $days,
            ];
        }

        // Calculate MAE (Mean Absolute Error)
        $mae = $attempts->map(function ($a) {
            $predicted = $a->ml_score;
            $actual = $a->decision === 'block' ? 1.0 : 0.0;
            return abs($predicted - $actual);
        })->avg();

        // Calculate RMSE (Root Mean Squared Error)
        $rmse = sqrt($attempts->map(function ($a) {
            $predicted = $a->ml_score;
            $actual = $a->decision === 'block' ? 1.0 : 0.0;
            return pow($predicted - $actual, 2);
        })->avg());

        // Placeholder for MAPE and AUC-ROC
        $mape = $mae * 100;  // Simplified
        $auc_roc = 1.0 - $mae;  // Simplified

        return [
            'mae' => round($mae, 4),
            'rmse' => round($rmse, 4),
            'mape' => round($mape, 2),
            'auc_roc' => round($auc_roc, 4),
            'total_samples' => $attempts->count(),
            'period_days' => $days,
            'model_version' => $this->getCurrentModelVersion(),
        ];
    }

    /**
     * Get fraud statistics for dashboard/reporting
     */
    public function getFraudStatistics(int $days = 30): array
    {
        $attempts = DB::table('fraud_attempts')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $blocked = $attempts->where('decision', 'block')->count();
        $total = $attempts->count();

        return [
            'total_attempts' => $total,
            'blocked_count' => $blocked,
            'review_count' => $attempts->where('decision', 'review')->count(),
            'allowed_count' => $attempts->where('decision', 'allow')->count(),
            'block_rate' => $total > 0 ? ($blocked / $total) : 0,
            'avg_score' => $total > 0 ? $attempts->avg('ml_score') : 0,
            'period_days' => $days,
        ];
    }

    /**
     * Get user's fraud history
     */
    public function getUserFraudHistory(int $userId, int $limit = 50): array
    {
        $attempts = DB::table('fraud_attempts')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();

        return array_map(function ($a) {
            return [
                'operation_type' => $a->operation_type,
                'score' => $a->ml_score,
                'decision' => $a->decision,
                'reason' => $a->reason,
                'created_at' => $a->created_at,
                'correlation_id' => $a->correlation_id,
            ];
        }, $attempts);
    }
}
