<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class FilterDto
{
    /**
     * @param array<string, int> $brands
     * @param array<string, int> $categories
     * @param array<string, int> $colors
     * @param array<string, array<string, int>> $specs
     */
    public function __construct(
        public array $brands,
        public array $categories,
        public array $colors,
        public array $specs,
        public array $priceRanges,
    ) {
    }

    public function toArray(): array
    {
        return [
            'brands' => $this->brands,
            'categories' => $this->categories,
            'colors' => $this->colors,
            'specs' => $this->specs,
            'price_ranges' => $this->priceRanges,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            brands: $data['brands'] ?? [],
            categories: $data['categories'] ?? [],
            colors: $data['colors'] ?? [],
            specs: $data['specs'] ?? [],
            priceRanges: $data['price_ranges'] ?? [],
        );
    }
}
