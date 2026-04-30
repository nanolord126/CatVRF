<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Events;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\DataCategory;

/**
 * GDPR Deletion Requested Domain Event
 *
 * Dispatched when a user exercises their Right to be Forgotten (GDPR Art.17 / 152-FZ Art.21).
 * Triggers the full deletion pipeline across all BigData tables and audit log.
 */
final class GDPRDeletionRequested
{
    public readonly CarbonImmutable $occurredAt;

    /**
     * @param int $userId The user requesting deletion
     * @param string $requestId Unique request identifier for tracking
     * @param array<DataCategory> $categories Data categories to be deleted
     * @param bool $isVerified Whether the user identity has been verified
     * @param string $legalBasis Legal basis for the request (gdpr_art17, 152fz_art21)
     * @param CarbonImmutable|null $deadline SLA deadline (30 days from request)
     * @param string|null $correlationId Distributed tracing correlation
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $requestId,
        public readonly array $categories,
        public readonly bool $isVerified,
        public readonly string $legalBasis,
        public readonly ?CarbonImmutable $deadline,
        public readonly ?string $correlationId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? CarbonImmutable::now();
    }

    /**
     * Create a standard GDPR deletion request
     */
    public static function forUser(int $userId, ?string $correlationId = null): self
    {
        $categories = array_filter(
            DataCategory::cases(),
            fn(DataCategory $c) => $c->supportsGDPRDeletion(),
        );

        return new self(
            userId: $userId,
            requestId: \Illuminate\Support\Str::uuid()->toString(),
            categories: $categories,
            isVerified: false,
            legalBasis: 'gdpr_art17',
            deadline: CarbonImmutable::now()->addDays(30),
            correlationId: $correlationId ?? \Illuminate\Support\Str::uuid()->toString(),
        );
    }

    /**
     * Create a 152-FZ deletion request (Russian federal law)
     */
    public static function forUser152FZ(int $userId, ?string $correlationId = null): self
    {
        return self::forUser($userId, $correlationId)
            ->withLegalBasis('152fz_art21');
    }

    /**
     * Create with verification confirmed
     */
    public function withVerified(): self
    {
        return new self(
            userId: $this->userId,
            requestId: $this->requestId,
            categories: $this->categories,
            isVerified: true,
            legalBasis: $this->legalBasis,
            deadline: $this->deadline,
            correlationId: $this->correlationId,
            occurredAt: $this->occurredAt,
        );
    }

    /**
     * Create with different legal basis
     */
    public function withLegalBasis(string $legalBasis): self
    {
        return new self(
            userId: $this->userId,
            requestId: $this->requestId,
            categories: $this->categories,
            isVerified: $this->isVerified,
            legalBasis: $legalBasis,
            deadline: $this->deadline,
            correlationId: $this->correlationId,
            occurredAt: $this->occurredAt,
        );
    }

    /**
     * Can the deletion proceed? (requires verification)
     */
    public function canProceed(): bool
    {
        return $this->isVerified;
    }

    /**
     * Is the SLA deadline approaching? (less than 7 days remaining)
     */
    public function isDeadlineApproaching(): bool
    {
        if ($this->deadline === null) {
            return false;
        }

        return $this->deadline->diffInDays(CarbonImmutable::now()) < 7;
    }

    /**
     * Is the SLA deadline breached?
     */
    public function isDeadlineBreached(): bool
    {
        if ($this->deadline === null) {
            return false;
        }

        return CarbonImmutable::now()->isAfter($this->deadline);
    }

    /**
     * Get category names for logging
     * @return array<string>
     */
    public function categoryNames(): array
    {
        return array_map(fn(DataCategory $c) => $c->value, $this->categories);
    }

    public function toArray(): array
    {
        return [
            'event' => 'gdpr_deletion_requested',
            'user_id' => $this->userId,
            'request_id' => $this->requestId,
            'categories' => $this->categoryNames(),
            'is_verified' => $this->isVerified,
            'legal_basis' => $this->legalBasis,
            'deadline' => $this->deadline?->toIso8601String(),
            'correlation_id' => $this->correlationId,
            'occurred_at' => $this->occurredAt->toIso8601String(),
            'can_proceed' => $this->canProceed(),
            'deadline_approaching' => $this->isDeadlineApproaching(),
            'deadline_breached' => $this->isDeadlineBreached(),
        ];
    }
}
