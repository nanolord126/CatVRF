<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * AML Check Entity for ФЗ-115 compliance.
 * 
 * Stores AML/KYC check results with 5-year retention requirement.
 * Includes risk scoring, KYC level determination, and Rosfinmonitoring reporting status.
 */
final readonly class AMLCheck
{
    public const KYC_LEVEL_SIMPLIFIED = 'simplified';
    public const KYC_LEVEL_FULL = 'full';
    public const KYC_LEVEL_ENHANCED = 'enhanced';

    public const RISK_LEVEL_LOW = 'low';
    public const RISK_LEVEL_MEDIUM = 'medium';
    public const RISK_LEVEL_HIGH = 'high';
    public const RISK_LEVEL_CRITICAL = 'critical';

    private function __construct(
        public string $uuid,
        public int $userId,
        public ?int $tenantId,
        public ?string $orderId,
        public int $amountKopecks,
        public string $currency,
        public float $riskScore, // 0.0 to 1.0
        public string $riskLevel,
        public string $kycLevel,
        public bool $requiresFullKYC,
        public bool $isReportedToRosfinmonitoring,
        public ?CarbonImmutable $reportedAt,
        public array $checkFactors, // JSON with velocity, geo, profile mismatch, etc.
        public ?string $reason, // If blocked or requires review
        public string $status, // 'passed', 'review', 'blocked'
        public CarbonImmutable $checkedAt,
    ) {}

    public static function create(
        int $userId,
        ?int $tenantId,
        ?string $orderId,
        int $amountKopecks,
        string $currency,
        float $riskScore,
        array $checkFactors,
    ): self {
        $riskLevel = self::determineRiskLevel($riskScore);
        $kycLevel = self::determineKYCLevel($amountKopecks, $riskScore, $checkFactors);
        $requiresFullKYC = $amountKopecks > 10_000_00 || $riskScore > 0.7; // > 100k RUB or high risk
        $status = $riskScore > 0.85 ? 'blocked' : ($riskScore > 0.65 ? 'review' : 'passed');

        return new self(
            uuid: Str::uuid()->toString(),
            userId: $userId,
            tenantId: $tenantId,
            orderId: $orderId,
            amountKopecks: $amountKopecks,
            currency: $currency,
            riskScore: $riskScore,
            riskLevel: $riskLevel,
            kycLevel: $kycLevel,
            requiresFullKYC: $requiresFullKYC,
            isReportedToRosfinmonitoring: false,
            reportedAt: null,
            checkFactors: $checkFactors,
            reason: $status !== 'passed' ? self::getReason($riskScore, $checkFactors) : null,
            status: $status,
            checkedAt: CarbonImmutable::now(),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            userId: (int) $data['user_id'],
            tenantId: $data['tenant_id'] ? (int) $data['tenant_id'] : null,
            orderId: $data['order_id'] ?? null,
            amountKopecks: (int) $data['amount_kopecks'],
            currency: $data['currency'],
            riskScore: (float) $data['risk_score'],
            riskLevel: $data['risk_level'],
            kycLevel: $data['kyc_level'],
            requiresFullKYC: (bool) $data['requires_full_kyc'],
            isReportedToRosfinmonitoring: (bool) $data['is_reported_to_rosfinmonitoring'],
            reportedAt: $data['reported_at'] ? CarbonImmutable::parse($data['reported_at']) : null,
            checkFactors: $data['check_factors'],
            reason: $data['reason'] ?? null,
            status: $data['status'],
            checkedAt: CarbonImmutable::parse($data['checked_at']),
        );
    }

    public function markAsReported(): self
    {
        return new self(
            uuid: $this->uuid,
            userId: $this->userId,
            tenantId: $this->tenantId,
            orderId: $this->orderId,
            amountKopecks: $this->amountKopecks,
            currency: $this->currency,
            riskScore: $this->riskScore,
            riskLevel: $this->riskLevel,
            kycLevel: $this->kycLevel,
            requiresFullKYC: $this->requiresFullKYC,
            isReportedToRosfinmonitoring: true,
            reportedAt: CarbonImmutable::now(),
            checkFactors: $this->checkFactors,
            reason: $this->reason,
            status: $this->status,
            checkedAt: $this->checkedAt,
        );
    }

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function requiresReview(): bool
    {
        return $this->status === 'review';
    }

    public function isReportable(): bool
    {
        // Report to Rosfinmonitoring if critical risk or amount > 1M RUB
        return $this->riskLevel === self::RISK_LEVEL_CRITICAL || $this->amountKopecks > 100_000_00;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'order_id' => $this->orderId,
            'amount_kopecks' => $this->amountKopecks,
            'currency' => $this->currency,
            'risk_score' => $this->riskScore,
            'risk_level' => $this->riskLevel,
            'kyc_level' => $this->kycLevel,
            'requires_full_kyc' => $this->requiresFullKYC,
            'is_reported_to_rosfinmonitoring' => $this->isReportedToRosfinmonitoring,
            'reported_at' => $this->reportedAt?->toDateTimeString(),
            'check_factors' => $this->checkFactors,
            'reason' => $this->reason,
            'status' => $this->status,
            'checked_at' => $this->checkedAt->toDateTimeString(),
        ];
    }

    private static function determineRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 0.85 => self::RISK_LEVEL_CRITICAL,
            $score >= 0.65 => self::RISK_LEVEL_HIGH,
            $score >= 0.4 => self::RISK_LEVEL_MEDIUM,
            default => self::RISK_LEVEL_LOW,
        };
    }

    private static function determineKYCLevel(int $amountKopecks, float $riskScore, array $factors): string
    {
        // Enhanced KYC for high risk or large amounts
        if ($amountKopecks > 1_000_000 || $riskScore > 0.7) {
            return self::KYC_LEVEL_ENHANCED;
        }

        // Full KYC for amounts > 100k or medium risk
        if ($amountKopecks > 100_000 || $riskScore > 0.4) {
            return self::KYC_LEVEL_FULL;
        }

        // Simplified KYC for low risk, small amounts
        return self::KYC_LEVEL_SIMPLIFIED;
    }

    private static function getReason(float $score, array $factors): ?string
    {
        $reasons = [];

        if ($score > 0.85) {
            $reasons[] = 'Critical fraud risk';
        } elseif ($score > 0.65) {
            $reasons[] = 'High fraud risk';
        }

        if ($factors['velocity_24h'] > 10) {
            $reasons[] = 'Excessive transaction velocity';
        }

        if ($factors['geo_mismatch'] ?? false) {
            $reasons[] = 'Geographic mismatch';
        }

        if ($factors['profile_mismatch'] ?? false) {
            $reasons[] = 'Profile behavior mismatch';
        }

        return $reasons ? implode('; ', $reasons) : null;
    }
}
