<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class FeatureVector
{
    /**
     * @param array<int, float> $values Dense feature values
     * @param array<string, float> $sparseValues Sparse feature key=>value pairs
     * @param int $version Feature schema version
     */
    public function __construct(
        private array $values,
        private array $sparseValues = [],
        private int $version = 1,
    ) {
        if (count($values) === 0 && count($sparseValues) === 0) {
            throw new InvalidArgumentException('Feature vector must contain at least one value.');
        }
    }

    public static function empty(): self
    {
        return new self([0.0], [], 0);
    }

    public static function fromDense(array $values, int $version = 1): self
    {
        return new self(array_values($values), [], $version);
    }

    public static function fromSparse(array $sparseValues, int $version = 1): self
    {
        return new self([], $sparseValues, $version);
    }

    public static function fromMixed(array $dense, array $sparse, int $version = 1): self
    {
        return new self(array_values($dense), $sparse, $version);
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getSparseValues(): array
    {
        return $this->sparseValues;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function dimension(): int
    {
        return count($this->values) + count($this->sparseValues);
    }

    public function cosineSimilarity(self $other): float
    {
        $a = $this->values;
        $b = $other->values;

        if (count($a) !== count($b) || count($a) === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0, $n = count($a); $i < $n; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $denom = sqrt($normA) * sqrt($normB);
        return $denom > 0 ? $dotProduct / $denom : 0.0;
    }

    public function toArray(): array
    {
        return [
            'values' => $this->values,
            'sparse' => $this->sparseValues,
            'version' => $this->version,
            'dimension' => $this->dimension(),
        ];
    }
}
