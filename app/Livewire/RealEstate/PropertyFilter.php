<?php declare(strict_types=1);

namespace App\Livewire\RealEstate;

use Livewire\Component;

/**
 * Class PropertyFilter
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 *
 * @package App\Livewire\RealEstate
 */
final class PropertyFilter extends Component
{
    private string $propertyType = '';
        private int $priceMin = 0;
        private int $priceMax = 100000000;
        private int $areaMin = 0;
        private int $areaMax = 500;
        private string $district = '';
        private array $filteredProperties = [];

        /**
         * Handle applyFilters operation.
         *
         * @throws \DomainException
         */
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
            return view('livewire.real-estate.property-filter');
        }
}
