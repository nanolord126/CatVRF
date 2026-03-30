<?php declare(strict_types=1);

namespace App\Livewire\Analytics\Components;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FilterPersistenceComponent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
