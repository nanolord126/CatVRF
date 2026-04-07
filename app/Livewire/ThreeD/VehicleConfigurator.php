<?php declare(strict_types=1);

namespace App\Livewire\ThreeD;

use Livewire\Component;

final class VehicleConfigurator extends Component
{
    private int $vehicleId;
    private array $vehicleData = [];
    private string $selectedColor = '#000000';
    private string $selectedInterior = 'black';
    private array $selectedOptions = [];
    private float $price = 0;

    public function mount(int $vehicleId): void
    {
        $this->vehicleId = $vehicleId;
        $this->loadVehicleData();
    }

    public function loadVehicleData(): void
    {
        $this->vehicleData = [
            'id' => $this->vehicleId,
            'brand' => 'Tesla',
            'model' => 'Model 3',
            'basePrice' => 45000,
            'colors' => ['#000000', '#FFFFFF', '#E82127', '#0080FF'],
            'interiors' => ['black', 'white', 'grey', 'beige'],
            'options' => [
                ['id' => 1, 'name' => 'Panoramic Roof', 'price' => 1500],
                ['id' => 2, 'name' => 'Premium Audio', 'price' => 3000],
                ['id' => 3, 'name' => 'Leather Seats', 'price' => 2500],
            ],
        ];

        $this->price = $this->vehicleData['basePrice'];
    }

    public function selectColor(string $color): void
    {
        $this->selectedColor = $color;
        $this->dispatch('color-changed', color: $color);
    }

    public function selectInterior(string $interior): void
    {
        $this->selectedInterior = $interior;
        $this->dispatch('interior-changed', interior: $interior);
    }

    public function toggleOption(int $optionId): void
    {
        if (in_array($optionId, $this->selectedOptions)) {
            $this->selectedOptions = array_diff($this->selectedOptions, [$optionId]);
            $this->price -= $this->vehicleData['options'][$optionId - 1]['price'];
        } else {
            $this->selectedOptions[] = $optionId;
            $this->price += $this->vehicleData['options'][$optionId - 1]['price'];
        }
    }

    public function render()
    {
        return view('livewire.three-d.vehicle-configurator');
    }
}
