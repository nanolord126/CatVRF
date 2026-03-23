<?php

declare(strict_types=1);

namespace App\Livewire\Analytics\Components;

use Livewire\Component;

/**
 * Компонент: Filter State Persistence
 * 
 * Сохраняет состояние фильтров в localStorage
 * Восстанавливает при загрузке
 */
final class FilterPersistenceComponent extends Component
{
    public string $storageKey = 'analytics_filters';
    public array $filters = [];

    public function saveFilter(string $key, mixed $value): void
    {
        $this->filters[$key] = $value;
        $this->dispatch('filter-saved', key: $key, value: $value);
    }

    public function loadFilters(): array
    {
        return $this->filters;
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->dispatch('filters-cleared');
    }

    public function render()
    {
        return view('livewire.analytics.components.filter-persistence-component');
    }
}
