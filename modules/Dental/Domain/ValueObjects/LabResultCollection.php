<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\ValueObjects;

use Modules\Dental\Domain\Entities\LabResult;

final readonly class LabResultCollection
{
    /** @var LabResult[] */
    public array $items;

    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    public function add(LabResult $result): self
    {
        $items = $this->items;
        $items[] = $result;

        return new self($items);
    }

    public function getAbnormal(): self
    {
        $items = array_filter(
            $this->items,
            fn(LabResult $result) => $result->isAbnormal
        );

        return new self($items);
    }

    public function hasAbnormalResults(): bool
    {
        foreach ($this->items as $result) {
            if ($result->isAbnormal) {
                return true;
            }
        }

        return false;
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
     * @return LabResult[]
     */
    public function all(): array
    {
        return $this->items;
    }
}
