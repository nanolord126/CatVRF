<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Class BonusAggregate
 *
 * Core domain aggregate handling specific bonus entity accrual instances bounding them 
 * uniquely tying owners, strict allocation conditions and tracking absolute consumption metrics.
 * Ensures rules like expiration and maximum burn rates validate completely transparently internally.
 */
final class BonusAggregate
{
    /**
     * @param string $id Unique universally distinguishable instance identifier.
     * @param string $ownerId Entity owning definitively tying consumption bounds safely.
     * @param BonusAmount $initialAmount The absolute starting baseline quantity granted explicitly.
     * @param BonusAmount $remainingAmount Live balancing dynamically decaying quantity bounds.
     * @param BonusType $type Strategic categorization restricting integration mappings internally.
     * @param string $correlationId Unified traceable operational audit sequence tracking strings.
     * @param DateTimeImmutable $issuedAt Granular timestamp noting exact genesis tracking.
     * @param DateTimeImmutable|null $expiresAt Hard stop temporal limit automatically invalidating balance bounds.
     */
    public function __construct(
        private readonly string $id,
        private readonly string $ownerId,
        private readonly BonusAmount $initialAmount,
        private BonusAmount $remainingAmount,
        private readonly BonusType $type,
        private readonly string $correlationId,
        private readonly DateTimeImmutable $issuedAt,
        private readonly ?DateTimeImmutable $expiresAt = null
    ) {
        if (empty($this->id) || empty($this->ownerId) || empty($this->correlationId)) {
            throw new DomainException("Bonus aggregates critically require fully qualified identity strings fundamentally.");
        }

        if ($this->remainingAmount->getAmount() > $this->initialAmount->getAmount()) {
            throw new DomainException("Remaining active bonus quantities logically cannot exceed intrinsic allocated baselines safely.");
        }
    }

    /**
     * Instantiates fresh explicitly newly issued bonus sequences bounding directly natively.
     *
     * @param string $id
     * @param string $ownerId
     * @param BonusAmount $amount
     * @param BonusType $type
     * @param string $correlationId
     * @param DateTimeImmutable|null $expiresAt
     * @return self
     */
    public static function award(
        string $id,
        string $ownerId,
        BonusAmount $amount,
        BonusType $type,
        string $correlationId,
        ?DateTimeImmutable $expiresAt = null
    ): self {
        return new self(
            $id,
            $ownerId,
            $amount,
            $amount,
            $type,
            $correlationId,
            new DateTimeImmutable(),
            $expiresAt
        );
    }

    /**
     * Validates and executes intrinsic consumption mechanisms applying partial mapping bounds safely.
     *
     * @param BonusAmount $amount Quantity demanded actively consuming bounded limits.
     * @param DateTimeImmutable $now Standardized contextual temporal checkpoint sequences.
     * @return void
     * @throws DomainException
     */
    public function consume(BonusAmount $amount, DateTimeImmutable $now = new DateTimeImmutable()): void
    {
        if ($this->isExpired($now)) {
            throw new DomainException("Cannot safely consume explicitly expired temporal bounds mapping instances internally.");
        }

        if ($this->remainingAmount->getAmount() < $amount->getAmount()) {
            throw new DomainException("Active consumption demand strongly exceeds functionally available bounded quantities intrinsically.");
        }

        $this->remainingAmount = $this->remainingAmount->subtract($amount);
    }

    /**
     * Validates temporal bounds defining if allocation logically persists natively securely.
     *
     * @param DateTimeImmutable $now
     * @return bool
     */
    public function isExpired(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $now > $this->expiresAt;
    }

    /**
     * Confirms structurally if bounded sequence fully natively empty rendering unusable functionally.
     *
     * @return bool
     */
    public function isFullyConsumed(): bool
    {
        return $this->remainingAmount->isZero();
    }

    /**
     * Extracts UUID mapping instance purely validating uniquely.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Retrieves owner uniquely assigning consumption scopes distinctly directly.
     *
     * @return string
     */
    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    /**
     * Fetches current state dynamically mapping bounded limits securely actively.
     *
     * @return BonusAmount
     */
    public function getRemainingAmount(): BonusAmount
    {
        return $this->remainingAmount;
    }

    /**
     * Yields structurally typing parameters effectively dynamically isolating mappings.
     *
     * @return BonusType
     */
    public function getType(): BonusType
    {
        return $this->type;
    }

    /**
     * Dumps traceability securely binding sequences externally propagating cleanly.
     *
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
