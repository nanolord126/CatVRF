<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class FilterConfigDto
{
    /**
     * @param array<int, array<string, mixed>> $primaryFilters
     * @param array<int, array<string, mixed>> $secondaryFilters
     * @param array<int, array<string, string>> $sortOptions
     */
    public function __construct(
        public string $type,
        public string $label,
        public string $icon,
        public array $primaryFilters,
        public array $secondaryFilters,
        public array $sortOptions,
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'icon' => $this->icon,
            'primary_filters' => $this->primaryFilters,
            'secondary_filters' => $this->secondaryFilters,
            'sort_options' => $this->sortOptions,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            label: $data['label'],
            icon: $data['icon'],
            primaryFilters: $data['primary_filters'] ?? [],
            secondaryFilters: $data['secondary_filters'] ?? [],
            sortOptions: $data['sort_options'] ?? [],
        );
    }
}
