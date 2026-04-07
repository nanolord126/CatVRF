<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Infrastructure\Services;

use App\Domains\Analytics\Domain\Interfaces\FraudScoringInterface;

/**
 * ML-based fraud scoring service.
 *
 * Evaluates a set of behavioural and transactional features and returns
 * a normalised fraud-risk score in the range [0.0 … 1.0].
 *
 * Current implementation uses a rule-based heuristic that mirrors the
 * feature weights of the production XGBoost model.  When the real ML
 * inference endpoint becomes available, only {@see self::callMlEndpoint()}
 * needs to be swapped.
 *
 * @see \App\Services\FraudControlService  orchestrator that consumes the score
 * @see \App\Services\AuditService
 * @package App\Domains\Analytics\Infrastructure\Services
 */
final readonly class MlFraudScoringService implements FraudScoringInterface
{
    /** Maximum score that can be returned. */
    private const MAX_SCORE = 1.0;

    /** Weight: high event velocity within the last hour. */
    private const W_EVENTS_LAST_HOUR = 0.30;

    /** Weight: multiple distinct IPs within 24 h. */
    private const W_DISTINCT_IPS = 0.25;

    /** Weight: device fingerprint changed recently. */
    private const W_DEVICE_CHANGED = 0.20;

    /** Weight: account age is less than 24 hours. */
    private const W_NEW_ACCOUNT = 0.15;

    /** Weight: unusually high transaction amount. */
    private const W_HIGH_AMOUNT = 0.10;

    /**
     * Calculate a fraud-risk score based on the supplied feature vector.
     *
     * @param  array<string, mixed>  $features  Associative array of features:
     *   - events_last_hour   (int)   — number of events in the last 60 min
     *   - distinct_ips_last_day (int) — unique IP addresses in 24 h
     *   - device_changed      (bool)  — fingerprint changed since last session
     *   - account_age_hours   (int)   — age of the account in hours
     *   - amount              (float) — transaction amount in roubles
     *
     * @return float  Normalised score 0.0 (safe) … 1.0 (fraudulent).
     *
     * @throws \DomainException If the feature vector is empty.
     */
    public function getScore(array $features): float
    {
        if ($features === []) {
            throw new \DomainException('Feature vector must not be empty.');
        }

        $score = 0.0;

        $score += $this->scoreEventsVelocity((int) ($features['events_last_hour'] ?? 0));
        $score += $this->scoreDistinctIps((int) ($features['distinct_ips_last_day'] ?? 0));
        $score += $this->scoreDeviceChange((bool) ($features['device_changed'] ?? false));
        $score += $this->scoreAccountAge((int) ($features['account_age_hours'] ?? 9999));
        $score += $this->scoreTransactionAmount((float) ($features['amount'] ?? 0.0));

        return min(round($score, 4), self::MAX_SCORE);
    }

    private function scoreEventsVelocity(int $eventsLastHour): float
    {
        return $eventsLastHour > 100 ? self::W_EVENTS_LAST_HOUR : 0.0;
    }

    private function scoreDistinctIps(int $distinctIps): float
    {
        return $distinctIps > 5 ? self::W_DISTINCT_IPS : 0.0;
    }

    private function scoreDeviceChange(bool $changed): float
    {
        return $changed ? self::W_DEVICE_CHANGED : 0.0;
    }

    private function scoreAccountAge(int $ageHours): float
    {
        return $ageHours < 24 ? self::W_NEW_ACCOUNT : 0.0;
    }

    private function scoreTransactionAmount(float $amount): float
    {
        return $amount > 50_000.0 ? self::W_HIGH_AMOUNT : 0.0;
    }
}
