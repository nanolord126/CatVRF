<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\ValueObjects;

use Modules\Dental\Domain\Entities\TreatmentStep;

final readonly class TreatmentStepCollection
{
    /** @var TreatmentStep[] */
    public array $items;

    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    public function add(TreatmentStep $step): self
    {
        $items = $this->items;
        $items[] = $step;

        return new self($items);
    }

    public function getTotalCost(): Money
    {
        $total = Money::zero();
        foreach ($this->items as $step) {
            $total = $total->add($step->cost);
        }

        return $total;
    }

    public function getByStatus(string $status): self
    {
        $items = array_filter(
            $this->items,
            fn(TreatmentStep $step) => $step->status->value === $status
        );

        return new self($items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * @return TreatmentStep[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function sortByOrder(): self
    {
        $items = $this->items;
        usort($items, fn(TreatmentStep $a, TreatmentStep $b) => $a->sortOrder <=> $b->sortOrder);

        return new self($items);
    }
}
