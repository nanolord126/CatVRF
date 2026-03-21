<?php declare(strict_types=1);

namespace App\Livewire\RealEstate;

use Livewire\Component;
use Illuminate\View\View;

final class PropertyFilter extends Component
{
    public string $propertyType = '';
    public int $priceMin = 0;
    public int $priceMax = 100000000;
    public int $areaMin = 0;
    public int $areaMax = 500;
    public string $district = '';
    public array $filteredProperties = [];

    public function applyFilters(): void
    {
        // In real app, query database with filters
        $this->filteredProperties = [
            ['id' => 1, 'name' => 'Квартира в ЦАО', 'price' => 1500000, 'area' => 50],
            ['id' => 2, 'name' => 'Дом в Подмосковье', 'price' => 3000000, 'area' => 120],
        ];
        $this->dispatch('filters-applied');
    }

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
