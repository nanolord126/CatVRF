<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Class BonusProgram
 *
 * Represents a bonus program configuration that defines rules and constraints
 * for awarding bonuses of a specific type. Programs can be time-limited, have
 * budget caps, and define eligibility criteria. Programs are the blueprint for
 * creating individual bonus aggregates.
 */
final class BonusProgram
{
    /**
     * @param  string  $id  Unique program identifier.
     * @param  string  $name  Human-readable program name.
     * @param  string  $code  Unique code for referencing the program (e.g., in APIs).
     * @param  BonusType  $type  The type of bonuses this program awards.
     * @param  BonusAmount  $maxAwardAmount  Maximum single award amount for this program.
     * @param  BonusAmount  $budgetCap  Total budget cap for the program (optional).
     * @param  BonusAmount  $budgetUsed  Total budget already used by this program.
     * @param  DateTimeImmutable  $startDate  When the program becomes active.
     * @param  DateTimeImmutable|null  $endDate  When the program ends (null for indefinite).
     * @param  bool  $isActive  Whether the program is currently active.
     * @param  array  $eligibilityRules  Rules defining who is eligible for this program.
     * @param  array  $verticals  Verticals where this program is applicable (empty = all).
     * @param  string|null  $description  Detailed description of the program.
     * @param  array  $metadata  Additional metadata for analytics and tracking.
     */
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $code,
        private readonly BonusType $type,
        private readonly BonusAmount $maxAwardAmount,
        private readonly BonusAmount $budgetCap,
        private BonusAmount $budgetUsed,
        private readonly DateTimeImmutable $startDate,
        private readonly ?DateTimeImmutable $endDate = null,
        private bool $isActive = true,
        private readonly array $eligibilityRules = [],
        private readonly array $verticals = [],
        private readonly ?string $description = null,
        private readonly array $metadata = []
    ) {
        $this->validate();
    }

    /**
     * Validates program invariants to ensure data integrity.
     */
    private function validate(): void
    {
        if (empty($this->id) || empty($this->name) || empty($this->code)) {
            throw new DomainException('Program ID, name, and code are required.');
        }

        if ($this->maxAwardAmount->getAmount() <= 0) {
            throw new DomainException('Maximum award amount must be positive.');
        }

        if ($this->budgetUsed->getAmount() < 0) {
            throw new DomainException('Budget used cannot be negative.');
        }

        if ($this->budgetUsed->getAmount() > $this->budgetCap->getAmount()) {
            throw new DomainException('Budget used cannot exceed budget cap.');
        }

        if ($this->endDate !== null && $this->endDate <= $this->startDate) {
            throw new DomainException('End date must be after start date.');
        }
    }

    /**
     * Creates a new bonus program from configuration data.
     */
    public static function create(array $config): self
    {
        return new self(
            id: $config['id'],
            name: $config['name'],
            code: $config['code'],
            type: BonusType::fromString($config['type']),
            maxAwardAmount: new BonusAmount((int) $config['max_award_amount']),
            budgetCap: new BonusAmount((int) ($config['budget_cap'] ?? PHP_INT_MAX)),
            budgetUsed: new BonusAmount(0),
            startDate: new DateTimeImmutable($config['start_date']),
            endDate: $config['end_date'] ? new DateTimeImmutable($config['end_date']) : null,
            isActive: $config['is_active'] ?? true,
            eligibilityRules: $config['eligibility_rules'] ?? [],
            verticals: $config['verticals'] ?? [],
            description: $config['description'] ?? null,
            metadata: $config['metadata'] ?? [],
        );
    }

    /**
     * Reconstructs program from persistence data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            code: $data['code'],
            type: BonusType::fromString($data['type']),
            maxAwardAmount: new BonusAmount((int) $data['max_award_amount']),
            budgetCap: new BonusAmount((int) $data['budget_cap']),
            budgetUsed: new BonusAmount((int) $data['budget_used']),
            startDate: new DateTimeImmutable($data['start_date']),
            endDate: $data['end_date'] ? new DateTimeImmutable($data['end_date']) : null,
            isActive: (bool) $data['is_active'],
            eligibilityRules: $data['eligibility_rules'] ?? [],
            verticals: $data['verticals'] ?? [],
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Checks if the program is currently active based on dates and status flag.
     */
    public function isActiveNow(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($now < $this->startDate) {
            return false;
        }

        if ($this->endDate !== null && $now > $this->endDate) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the program has exhausted its budget.
     */
    public function isBudgetExhausted(): bool
    {
        return $this->budgetUsed->getAmount() >= $this->budgetCap->getAmount();
    }

    /**
     * Checks if the program can award the requested amount.
     * Considers both max award amount and remaining budget.
     */
    public function canAwardAmount(BonusAmount $amount): bool
    {
        if ($amount->getAmount() > $this->maxAwardAmount->getAmount()) {
            return false;
        }

        if ($this->isBudgetExhausted()) {
            return false;
        }

        $remainingBudget = $this->budgetCap->getAmount() - $this->budgetUsed->getAmount();

        return $amount->getAmount() <= $remainingBudget;
    }

    /**
     * Records a bonus award against the program budget.
     *
     * @param  BonusAmount  $amount  The amount awarded.
     * @throws DomainException If award exceeds remaining budget.
     */
    public function recordAward(BonusAmount $amount): void
    {
        if (!$this->canAwardAmount($amount)) {
            throw new DomainException('Cannot award amount: exceeds program limits or budget.');
        }

        $this->budgetUsed = $this->budgetUsed->add($amount);
    }

    /**
     * Checks if a vertical is eligible for this program.
     * Returns true if verticals list is empty (universal program) or vertical is in list.
     */
    public function isVerticalEligible(?string $vertical): bool
    {
        if (empty($this->verticals)) {
            return true; // Universal program
        }

        if ($vertical === null) {
            return false;
        }

        return in_array($vertical, $this->verticals, true);
    }

    /**
     * Checks eligibility based on provided context and program rules.
     */
    public function isEligible(array $context): bool
    {
        if (!$this->isActiveNow()) {
            return false;
        }

        if (!$this->isVerticalEligible($context['vertical'] ?? null)) {
            return false;
        }

        // Apply custom eligibility rules if defined
        foreach ($this->eligibilityRules as $rule) {
            if (!$this->evaluateEligibilityRule($rule, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluates a single eligibility rule against context.
     */
    private function evaluateEligibilityRule(array $rule, array $context): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? null;

        if ($field === null) {
            return true;
        }

        $contextValue = $context[$field] ?? null;

        return match ($operator) {
            'equals' => $contextValue == $value,
            'not_equals' => $contextValue != $value,
            'greater_than' => $contextValue > $value,
            'less_than' => $contextValue < $value,
            'greater_or_equal' => $contextValue >= $value,
            'less_or_equal' => $contextValue <= $value,
            'in' => in_array($contextValue, (array) $value, true),
            'not_in' => !in_array($contextValue, (array) $value, true),
            'contains' => str_contains((string) $contextValue, (string) $value),
            'starts_with' => str_starts_with((string) $contextValue, (string) $value),
            'ends_with' => str_ends_with((string) $contextValue, (string) $value),
            default => true,
        };
    }

    /**
     * Activates the program.
     */
    public function activate(): void
    {
        $this->isActive = true;
    }

    /**
     * Deactivates the program.
     */
    public function deactivate(): void
    {
        $this->isActive = false;
    }

    /**
     * Returns remaining budget for the program.
     */
    public function getRemainingBudget(): BonusAmount
    {
        $remaining = $this->budgetCap->getAmount() - $this->budgetUsed->getAmount();

        return new BonusAmount(max(0, $remaining));
    }

    /**
     * Returns budget utilization percentage.
     */
    public function getBudgetUtilizationPercentage(): float
    {
        if ($this->budgetCap->getAmount() === 0) {
            return 100.0;
        }

        return ($this->budgetUsed->getAmount() / $this->budgetCap->getAmount()) * 100;
    }

    /**
     * Returns days until program ends.
     * Returns null if program has no end date or has already ended.
     */
    public function getDaysUntilEnd(DateTimeImmutable $now = new DateTimeImmutable()): ?int
    {
        if ($this->endDate === null) {
            return null;
        }

        if ($now > $this->endDate) {
            return 0;
        }

        $interval = $now->diff($this->endDate);

        return $interval->days;
    }

    /**
     * Checks if program is ending soon (within 7 days).
     */
    public function isEndingSoon(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        $days = $this->getDaysUntilEnd($now);

        return $days !== null && $days <= 7 && $days > 0;
    }

    // Getters

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): BonusType
    {
        return $this->type;
    }

    public function getMaxAwardAmount(): BonusAmount
    {
        return $this->maxAwardAmount;
    }

    public function getBudgetCap(): BonusAmount
    {
        return $this->budgetCap;
    }

    public function getBudgetUsed(): BonusAmount
    {
        return $this->budgetUsed;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function isProgramActive(): bool
    {
        return $this->isActive;
    }

    public function getEligibilityRules(): array
    {
        return $this->eligibilityRules;
    }

    public function getVerticals(): array
    {
        return $this->verticals;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Converts program to array for persistence.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type->value,
            'max_award_amount' => $this->maxAwardAmount->getAmount(),
            'budget_cap' => $this->budgetCap->getAmount(),
            'budget_used' => $this->budgetUsed->getAmount(),
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'end_date' => $this->endDate?->format('Y-m-d H:i:s'),
            'is_active' => $this->isActive,
            'eligibility_rules' => $this->eligibilityRules,
            'verticals' => $this->verticals,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}
