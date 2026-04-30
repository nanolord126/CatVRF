<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * RFM Score Data Transfer Object
 *
 * Represents the result of RFM (Recency, Frequency, Monetary) analysis
 * for customer segmentation. RFM analysis is a marketing technique used to
 * quantitatively rank and group customers based on the recency, frequency and
 * monetary total of their recent transactions to identify the best customers
 * and perform targeted marketing campaigns.
 *
 * - Recency: How recently a customer has made a purchase
 * - Frequency: How often a customer makes a purchase
 * - Monetary: How much money a customer spends on purchases
 *
 * @package Modules\Analytics\Application\DTOs
 */
final readonly class RFMScoreDto
{
    private const SEGMENT_CHAMPIONS = 'Champions';
    private const SEGMENT_LOYAL = 'Loyal Customers';
    private const SEGMENT_POTENTIAL = 'Potential Loyalists';
    private const SEGMENT_NEW = 'New Customers';
    private const SEGMENT_PROMISING = 'Promising';
    private const SEGMENT_NEEDS_ATTENTION = 'Needs Attention';
    private const SEGMENT_ABOUT_TO_SLEEP = 'About to Sleep';
    private const SEGMENT_AT_RISK = 'At Risk';
    private const SEGMENT_CANNOT_LOSE = 'Cannot Lose Them';
    private const SEGMENT_HIBERNATING = 'Hibernating';
    private const SEGMENT_LOST = 'Lost';

    /**
     * Create a new RFMScoreDto instance.
     *
     * @param int $userId The ID of the customer
     * @param int $recency Days since the last order (lower is better)
     * @param int $frequency Number of orders in the analysis period (higher is better)
     * @param float $monetary Total monetary value spent (higher is better)
     * @param string $segment The customer segment classification
     * @param int $recencyScore Normalized recency score (1-5)
     * @param int $frequencyScore Normalized frequency score (1-5)
     * @param int $monetaryScore Normalized monetary score (1-5)
     * @param float $overallScore Overall RFM score (0-100)
     * @param \DateTimeImmutable|null $calculatedAt When the score was calculated
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $recency,
        public readonly int $frequency,
        public readonly float $monetary,
        public readonly string $segment,
        public readonly int $recencyScore = 0,
        public readonly int $frequencyScore = 0,
        public readonly int $monetaryScore = 0,
        public readonly float $overallScore = 0.0,
        public readonly ?\DateTimeImmutable $calculatedAt = null,
    ) {
        $this->validate();
    }

    /**
     * Create DTO from array data.
     *
     * @param array $data The data to create the DTO from
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) ($data['user_id'] ?? 0),
            recency: (int) ($data['recency'] ?? 0),
            frequency: (int) ($data['frequency'] ?? 0),
            monetary: (float) ($data['monetary'] ?? 0.0),
            segment: (string) ($data['segment'] ?? 'Unknown'),
            recencyScore: (int) ($data['recency_score'] ?? 0),
            frequencyScore: (int) ($data['frequency_score'] ?? 0),
            monetaryScore: (int) ($data['monetary_score'] ?? 0),
            overallScore: (float) ($data['overall_score'] ?? 0.0),
            calculatedAt: isset($data['calculated_at']) 
                ? \DateTimeImmutable::createFromFormat(\DateTime::ATOM, $data['calculated_at']) 
                : null,
        );
    }

    /**
     * Convert DTO to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'recency' => $this->recency,
            'frequency' => $this->frequency,
            'monetary' => $this->monetary,
            'segment' => $this->segment,
            'recency_score' => $this->recencyScore,
            'frequency_score' => $this->frequencyScore,
            'monetary_score' => $this->monetaryScore,
            'overall_score' => $this->overallScore,
            'calculated_at' => $this->calculatedAt?->format(\DateTime::ATOM),
        ];
    }

    /**
     * Validate the DTO data.
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    private function validate(): void
    {
        if ($this->userId <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }

        if ($this->recency < 0) {
            throw new \InvalidArgumentException('Recency cannot be negative');
        }

        if ($this->frequency < 0) {
            throw new \InvalidArgumentException('Frequency cannot be negative');
        }

        if ($this->monetary < 0) {
            throw new \InvalidArgumentException('Monetary value cannot be negative');
        }

        $validSegments = [
            self::SEGMENT_CHAMPIONS,
            self::SEGMENT_LOYAL,
            self::SEGMENT_POTENTIAL,
            self::SEGMENT_NEW,
            self::SEGMENT_PROMISING,
            self::SEGMENT_NEEDS_ATTENTION,
            self::SEGMENT_ABOUT_TO_SLEEP,
            self::SEGMENT_AT_RISK,
            self::SEGMENT_CANNOT_LOSE,
            self::SEGMENT_HIBERNATING,
            self::SEGMENT_LOST,
        ];

        if (!in_array($this->segment, $validSegments, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid segment: %s. Valid segments are: %s', $this->segment, implode(', ', $validSegments))
            );
        }
    }

    /**
     * Check if the customer is a high-value customer.
     *
     * @return bool
     */
    public function isHighValue(): bool
    {
        return in_array($this->segment, [
            self::SEGMENT_CHAMPIONS,
            self::SEGMENT_LOYAL,
            self::SEGMENT_CANNOT_LOSE,
        ], true);
    }

    /**
     * Check if the customer is at risk of churning.
     *
     * @return bool
     */
    public function isAtRisk(): bool
    {
        return in_array($this->segment, [
            self::SEGMENT_AT_RISK,
            self::SEGMENT_HIBERNATING,
            self::SEGMENT_LOST,
        ], true);
    }

    /**
     * Check if the customer is new.
     *
     * @return bool
     */
    public function isNewCustomer(): bool
    {
        return $this->segment === self::SEGMENT_NEW;
    }

    /**
     * Get the recommended action for this customer segment.
     *
     * @return string
     */
    public function getRecommendedAction(): string
    {
        return match ($this->segment) {
            self::SEGMENT_CHAMPIONS => 'Offer exclusive VIP rewards and early access',
            self::SEGMENT_LOYAL => 'Provide loyalty points and personalized offers',
            self::SEGMENT_POTENTIAL, self::SEGMENT_PROMISING => 'Encourage with membership programs',
            self::SEGMENT_NEW => 'Onboard with welcome offers and education',
            self::SEGMENT_NEEDS_ATTENTION => 'Re-engage with personalized recommendations',
            self::SEGMENT_ABOUT_TO_SLEEP => 'Send win-back campaigns with discounts',
            self::SEGMENT_AT_RISK => 'Offer special incentives to prevent churn',
            self::SEGMENT_CANNOT_LOSE => 'Personal outreach to understand concerns',
            self::SEGMENT_HIBERNATING => 'Reactivation campaigns with strong offers',
            self::SEGMENT_LOST => 'Analyze reasons for churn and prevent future losses',
            default => 'Standard engagement strategy',
        };
    }

    /**
     * Create a DTO with updated scores.
     *
     * @param int $recencyScore
     * @param int $frequencyScore
     * @param int $monetaryScore
     * @param float $overallScore
     * @return self
     */
    public function withScores(int $recencyScore, int $frequencyScore, int $monetaryScore, float $overallScore): self
    {
        return new self(
            userId: $this->userId,
            recency: $this->recency,
            frequency: $this->frequency,
            monetary: $this->monetary,
            segment: $this->segment,
            recencyScore: $recencyScore,
            frequencyScore: $frequencyScore,
            monetaryScore: $monetaryScore,
            overallScore: $overallScore,
            calculatedAt: $this->calculatedAt,
        );
    }

    /**
     * Check if this DTO equals another DTO.
     *
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->userId === $other->userId
            && $this->recency === $other->recency
            && $this->frequency === $other->frequency
            && $this->monetary === $other->monetary
            && $this->segment === $other->segment;
    }

    /**
     * Get all valid segment names.
     *
     * @return array<string>
     */
    public static function getValidSegments(): array
    {
        return [
            self::SEGMENT_CHAMPIONS,
            self::SEGMENT_LOYAL,
            self::SEGMENT_POTENTIAL,
            self::SEGMENT_NEW,
            self::SEGMENT_PROMISING,
            self::SEGMENT_NEEDS_ATTENTION,
            self::SEGMENT_ABOUT_TO_SLEEP,
            self::SEGMENT_AT_RISK,
            self::SEGMENT_CANNOT_LOSE,
            self::SEGMENT_HIBERNATING,
            self::SEGMENT_LOST,
        ];
    }
}
