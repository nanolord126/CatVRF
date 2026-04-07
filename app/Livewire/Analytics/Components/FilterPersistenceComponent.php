<?php declare(strict_types=1);

namespace App\Livewire\Analytics\Components;

use Livewire\Component;

/**
 * Class FilterPersistenceComponent
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 *
 * @package App\Livewire\Analytics\Components
 */
final class FilterPersistenceComponent extends Component
{
    private string $storageKey = 'analytics_filters';
        private array $filters = [];

        /**
         * Handle saveFilter operation.
         *
         * @throws \DomainException
         */
        public function saveFilter(string $key, mixed $value): void
        {
            $this->filters[$key] = $value;
            $this->dispatch('filter-saved', key: $key, value: $value);
        }

        /**
         * Handle loadFilters operation.
         *
         * @throws \DomainException
         */
        public function loadFilters(): array
        {
            return $this->filters;
        }

        /**
         * Handle clearFilters operation.
         *
         * @throws \DomainException
         */
        public function clearFilters(): void
        {
            $this->filters = [];
            $this->dispatch('filters-cleared');
        }

        /**
         * Handle render operation.
         *
         * @throws \DomainException
         */
        public function render()
        {
            return view('livewire.analytics.components.filter-persistence-component');
        }
}
