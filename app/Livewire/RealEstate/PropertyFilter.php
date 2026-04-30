<?php

declare(strict_types=1);

namespace App\Livewire\RealEstate;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

/**
 * Class PropertyFilter
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 */
final class PropertyFilter extends Component
{
    private readonly string $propertyType = '';

    private readonly int $priceMin = 0;

    private readonly int $priceMax = 100000000;

    private readonly int $areaMin = 0;

    private readonly int $areaMax = 500;

    private readonly string $district = '';

    private readonly array $filteredProperties = [];

    /**
     * Handle applyFilters operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function applyFilters(): void
    {
        // In real app, query database with filters
        $this->filteredProperties = [
            ['id' => 1, 'name' => 'Квартира в ЦАО', 'price' => 1500000, 'area' => 50],
            ['id' => 2, 'name' => 'Дом в Подмосковье', 'price' => 3000000, 'area' => 120],
        ];
        $this->dispatch('filters-applied');
    }

    /**
     * Handle resetFilters operation.
     *
     * @throws \DomainException
     */
    public function resetFilters(): void
    {
        $this->propertyType = '';
        $this->priceMin = 0;
        $this->priceMax = 100000000;
        $this->areaMin = 0;
        $this->areaMax = 500;
        $this->district = '';
        $this->filteredProperties = [];
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.real-estate.property-filter');
    }
}
