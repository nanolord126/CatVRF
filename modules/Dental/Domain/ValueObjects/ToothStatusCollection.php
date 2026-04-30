<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\ValueObjects;

use Modules\Dental\Domain\Entities\ToothStatus;

final readonly class ToothStatusCollection
{
    /** @var ToothStatus[] */
    public array $items;

    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    public function add(ToothStatus $toothStatus): self
    {
        $items = $this->items;
        $items[] = $toothStatus;

        return new self($items);
    }

    public function remove(string $toothNumber): self
    {
        $items = array_filter(
            $this->items,
            fn(ToothStatus $ts) => $ts->toothNumber !== $toothNumber
        );

        return new self($items);
    }

    public function getByToothNumber(string $toothNumber): ?ToothStatus
    {
        foreach ($this->items as $toothStatus) {
            if ($toothStatus->toothNumber === $toothNumber) {
                return $toothStatus;
            }
        }

        return null;
    }

    public function hasTooth(string $toothNumber): bool
    {
        return $this->getByToothNumber($toothNumber) !== null;
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
     * @return ToothStatus[]
     */
    public function all(): array
    {
        return $this->items;
    }
}
