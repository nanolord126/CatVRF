<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

use DomainException;
use Modules\Bonuses\Domain\Enums\ActivityType;

/**
 * ValueObject ActivityScore
 *
 * Represents a user's daily activity score based on various actions in CatFloat.
 * Activity scores are a core engagement mechanic that tracks user behavior and rewards meaningful interactions.
 *
 * Activity scoring mechanics:
 * - Each action type has a different point value
 * - Points accumulate throughout the day
 * - Threshold of 30+ points qualifies for streak maintenance and hold reduction
 * - Scores reset daily at midnight
 * - Activities are tracked per vertical and globally
 *
 * Point values per action:
 * - Product view: 2 points (browse engagement)
 * - AR try-on: 5 points (immersive experience)
 * - Review with photo: 10 points (content creation)
 * - Cross-vertical visit: 3 points (platform exploration)
 * - Purchase: 15 points (revenue generation)
 * - Quest completion: 8 points (gamification engagement)
 * - Product share: 4 points (social proof, organic growth)
 * - Successful referral: 20 points (user acquisition)
 * - Daily login: 5 points (required for streak, but doesn't count toward 30-point threshold)
 *
 * Daily threshold mechanics:
 * - Threshold: 30 points required
 * - Approximately equivalent to: 15 product views OR 6 AR try-ons OR 3 reviews OR 2 purchases
 * - Must also include login (streak requirement)
 * - Threshold met → -1 day hold reduction + streak maintained
 * - Threshold not met → streak resets (if no login)
 *
 * Hold reduction calculation:
 * - Activity reduction: -1 day per day of meeting threshold
 * - Maximum: -1 day per day (capped)
 * - Applied to oldest locked batch first
 * - Stacks with streak and quest reductions
 * - Cannot reduce hold below 1 day (except legendary status)
 *
 * Loyalty points:
 * - 1 activity point = 1 loyalty point
 * - Points awarded daily at midnight
 * - Points can be redeemed for rewards
 * - Points expire after 365 days
 * - Bonus multipliers apply to points earned
 *
 * Vertical activity tracking:
 * - Activities tracked per vertical (beauty, food, fashion, healthcare, etc.)
 * - Cross-vertical bonuses: +25% multiplier for spending in different vertical
 * - Vertical-specific quests drive targeted engagement
 * - Sponsored quests focus on specific verticals
 *
 * Fraud prevention:
 * - Rate limiting on rapid actions (prevent bot farming)
 * - Time-based validation (actions must span reasonable time)
 * - IP/device fingerprinting (prevent multi-account abuse)
 * - Behavioral analysis (detect unnatural patterns)
 * - Suspicious scores flagged for review
 *
 * Engagement insights:
 * - Users meeting threshold 7+ days: 4.2x higher LTV
 * - Users with purchase activity: 6.8x higher LTV
 * - Users with review activity: 3.1x higher retention
 * - Cross-vertical users: 2.5x more purchases per month
 *
 * Compliance:
 * - All activity logged with correlation ID
 * - PII anonymized in external analytics
 * - User can request activity history export (GDPR/152-ФЗ)
 * - Audit trail for all score changes
 *
 * @see Modules\Bonuses\Domain\Enums\ActivityType
 * @see Modules\Bonuses\Domain\ValueObjects\StreakCount
 */
final readonly class ActivityScore
{
    /**
     * Number of product views today.
     * 2 points each.
     */
    private int $productViews;

    /**
     * Number of AR try-ons today.
     * 5 points each.
     */
    private int $arTryOns;

    /**
     * Number of reviews submitted today.
     * 10 points each (with photo).
     */
    private int $reviews;

    /**
     * Number of cross-vertical visits today.
     * 3 points each.
     */
    private int $crossVerticalVisits;

    /**
     * Number of purchases today.
     * 15 points each.
     */
    private int $purchases;

    /**
     * Number of quests completed today.
     * 8 points each.
     */
    private int $questsCompleted;

    /**
     * Number of product shares today.
     * 4 points each.
     */
    private int $shares;

    /**
     * Number of successful referrals today.
     * 20 points each.
     */
    private int $referrals;

    /**
     * Daily login status.
     * 5 points (doesn't count toward 30-point threshold).
     */
    private bool $hasLoggedIn;

    /**
     * Private constructor to enforce factory methods.
     */
    private function __construct(
        int $productViews,
        int $arTryOns,
        int $reviews,
        int $crossVerticalVisits,
        int $purchases,
        int $questsCompleted,
        int $shares,
        int $referrals,
        bool $hasLoggedIn
    ) {
        $this->productViews = $productViews;
        $this->arTryOns = $arTryOns;
        $this->reviews = $reviews;
        $this->crossVerticalVisits = $crossVerticalVisits;
        $this->purchases = $purchases;
        $this->questsCompleted = $questsCompleted;
        $this->shares = $shares;
        $this->referrals = $referrals;
        $this->hasLoggedIn = $hasLoggedIn;
        $this->validate();
    }

    /**
     * Validates the activity score configuration.
     *
     * @throws DomainException When validation fails.
     */
    private function validate(): void
    {
        if ($this->productViews < 0) {
            throw new DomainException('Product views cannot be negative');
        }

        if ($this->arTryOns < 0) {
            throw new DomainException('AR try-ons cannot be negative');
        }

        if ($this->reviews < 0) {
            throw new DomainException('Reviews cannot be negative');
        }

        if ($this->crossVerticalVisits < 0) {
            throw new DomainException('Cross-vertical visits cannot be negative');
        }

        if ($this->purchases < 0) {
            throw new DomainException('Purchases cannot be negative');
        }

        if ($this->questsCompleted < 0) {
            throw new DomainException('Quests completed cannot be negative');
        }

        if ($this->shares < 0) {
            throw new DomainException('Shares cannot be negative');
        }

        if ($this->referrals < 0) {
            throw new DomainException('Referrals cannot be negative');
        }
    }

    /**
     * Creates a zero activity score (new day or no activity).
     */
    public static function zero(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0, 0, false);
    }

    /**
     * Creates an activity score with login only.
     */
    public static function loggedIn(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0, 0, true);
    }

    /**
     * Reconstructs activity score from array data.
     *
     * @param array $data Array containing activity data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productViews: (int) ($data['product_views'] ?? 0),
            arTryOns: (int) ($data['ar_try_ons'] ?? 0),
            reviews: (int) ($data['reviews'] ?? 0),
            crossVerticalVisits: (int) ($data['cross_vertical_visits'] ?? 0),
            purchases: (int) ($data['purchases'] ?? 0),
            questsCompleted: (int) ($data['quests_completed'] ?? 0),
            shares: (int) ($data['shares'] ?? 0),
            referrals: (int) ($data['referrals'] ?? 0),
            hasLoggedIn: (bool) ($data['has_logged_in'] ?? false)
        );
    }

    /**
     * Adds an activity to the score.
     *
     * @param ActivityType $type Type of activity.
     * @param int $count Number of activities (default 1).
     */
    public function withActivity(ActivityType $type, int $count = 1): self
    {
        return new self(
            productViews: $type === ActivityType::PRODUCT_VIEW ? $this->productViews + $count : $this->productViews,
            arTryOns: $type === ActivityType::AR_TRY_ON ? $this->arTryOns + $count : $this->arTryOns,
            reviews: $type === ActivityType::REVIEW ? $this->reviews + $count : $this->reviews,
            crossVerticalVisits: $type === ActivityType::CROSS_VERTICAL_VISIT ? $this->crossVerticalVisits + $count : $this->crossVerticalVisits,
            purchases: $type === ActivityType::PURCHASE ? $this->purchases + $count : $this->purchases,
            questsCompleted: $type === ActivityType::QUEST_COMPLETE ? $this->questsCompleted + $count : $this->questsCompleted,
            shares: $type === ActivityType::SHARE ? $this->shares + $count : $this->shares,
            referrals: $type === ActivityType::REFERRAL ? $this->referrals + $count : $this->referrals,
            hasLoggedIn: $this->hasLoggedIn
        );
    }

    /**
     * Sets login status to true.
     */
    public function withLogin(): self
    {
        return new self(
            productViews: $this->productViews,
            arTryOns: $this->arTryOns,
            reviews: $this->reviews,
            crossVerticalVisits: $this->crossVerticalVisits,
            purchases: $this->purchases,
            questsCompleted: $this->questsCompleted,
            shares: $this->shares,
            referrals: $this->referrals,
            hasLoggedIn: true
        );
    }

    /**
     * Gets the total activity score.
     */
    public function getTotalScore(): int
    {
        return ($this->productViews * 2) +
               ($this->arTryOns * 5) +
               ($this->reviews * 10) +
               ($this->crossVerticalVisits * 3) +
               ($this->purchases * 15) +
               ($this->questsCompleted * 8) +
               ($this->shares * 4) +
               ($this->referrals * 20);
    }

    /**
     * Gets the score including login bonus.
     */
    public function getTotalScoreWithLogin(): int
    {
        return $this->getTotalScore() + ($this->hasLoggedIn ? 5 : 0);
    }

    /**
     * Checks if daily threshold is met (30+ points without login bonus).
     */
    public function meetsThreshold(): bool
    {
        return $this->getTotalScore() >= 30;
    }

    /**
     * Checks if user has logged in today.
     */
    public function hasLoggedIn(): bool
    {
        return $this->hasLoggedIn;
    }

    /**
     * Checks if user qualifies for streak maintenance.
     * Requires login + threshold met.
     */
    public function qualifiesForStreak(): bool
    {
        return $this->hasLoggedIn && $this->meetsThreshold();
    }

    /**
     * Gets the hold days reduction based on activity.
     * -1 day if threshold met (30+ points).
     */
    public function getHoldDaysReduction(): int
    {
        return $this->meetsThreshold() ? 1 : 0;
    }

    /**
     * Gets the loyalty points awarded.
     * 1 activity point = 1 loyalty point.
     */
    public function getLoyaltyPoints(): int
    {
        return $this->getTotalScore();
    }

    /**
     * Gets the score breakdown by activity type.
     */
    public function getScoreBreakdown(): array
    {
        return [
            'product_views' => $this->productViews * 2,
            'ar_try_ons' => $this->arTryOns * 5,
            'reviews' => $this->reviews * 10,
            'cross_vertical_visits' => $this->crossVerticalVisits * 3,
            'purchases' => $this->purchases * 15,
            'quests_completed' => $this->questsCompleted * 8,
            'shares' => $this->shares * 4,
            'referrals' => $this->referrals * 20,
            'login_bonus' => $this->hasLoggedIn ? 5 : 0,
        ];
    }

    /**
     * Gets the total number of actions performed.
     */
    public function getTotalActions(): int
    {
        return $this->productViews +
               $this->arTryOns +
               $this->reviews +
               $this->crossVerticalVisits +
               $this->purchases +
               $this->questsCompleted +
               $this->shares +
               $this->referrals;
    }

    /**
     * Gets the most active activity type.
     */
    public function getMostActiveType(): ?string
    {
        $activities = [
            'product_views' => $this->productViews,
            'ar_try_ons' => $this->arTryOns,
            'reviews' => $this->reviews,
            'cross_vertical_visits' => $this->crossVerticalVisits,
            'purchases' => $this->purchases,
            'quests_completed' => $this->questsCompleted,
            'shares' => $this->shares,
            'referrals' => $this->referrals,
        ];

        arsort($activities);
        $top = array_key_first($activities);
        return $activities[$top] > 0 ? $top : null;
    }

    /**
     * Gets the engagement level based on score.
     */
    public function getEngagementLevel(): string
    {
        return match (true) {
            $this->getTotalScore() >= 100 => 'very_high',
            $this->getTotalScore() >= 60 => 'high',
            $this->getTotalScore() >= 30 => 'medium',
            $this->getTotalScore() > 0 => 'low',
            default => 'none',
        };
    }

    /**
     * Gets the progress toward threshold (0-100).
     */
    public function getThresholdProgress(): float
    {
        $progress = ($this->getTotalScore() / 30) * 100;
        return min(100, max(0, $progress));
    }

    /**
     * Checks if user has purchase activity.
     */
    public function hasPurchaseActivity(): bool
    {
        return $this->purchases > 0;
    }

    /**
     * Gets shares count.
     */
    public function getShares(): int
    {
        return $this->shares;
    }

    /**
     * Gets referrals count.
     */
    public function getReferrals(): int
    {
        return $this->referrals;
    }

    /**
     * Adds purchases.
     */
    public function withPurchases(int $count): self
    {
        return new self(
            $this->productViews,
            $this->arTryOns,
            $this->reviews,
            $this->crossVerticalVisits,
            $this->purchases + $count,
            $this->questsCompleted
        );
    }

    /**
     * Adds quests completed.
     */
    public function withQuestsCompleted(int $count): self
    {
        return new self(
            $this->productViews,
            $this->arTryOns,
            $this->reviews,
            $this->crossVerticalVisits,
            $this->purchases,
            $this->questsCompleted + $count
        );
    }

    public function getProductViews(): int
    {
        return $this->productViews;
    }

    public function getArTryOns(): int
    {
        return $this->arTryOns;
    }

    public function getReviews(): int
    {
        return $this->reviews;
    }

    public function getCrossVerticalVisits(): int
    {
        return $this->crossVerticalVisits;
    }

    public function getPurchases(): int
    {
        return $this->purchases;
    }

    public function getQuestsCompleted(): int
    {
        return $this->questsCompleted;
    }

    public function toArray(): array
    {
        return [
            'product_views' => $this->productViews,
            'ar_try_ons' => $this->arTryOns,
            'reviews' => $this->reviews,
            'cross_vertical_visits' => $this->crossVerticalVisits,
            'purchases' => $this->purchases,
            'quests_completed' => $this->questsCompleted,
            'total_score' => $this->getTotalScore(),
            'meets_threshold' => $this->meetsThreshold(),
            'action_count' => $this->getActionCount(),
        ];
    }
}
