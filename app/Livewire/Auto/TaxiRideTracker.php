declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Livewire\Auto;

use Livewire\Component;
use Illuminate\View\View;

final /**
 * TaxiRideTracker
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TaxiRideTracker extends Component
{
    public string $rideId;
    public string $driverName = '';
    public string $vehicleLicense = '';
    public float $driverLat = 0;
    public float $driverLon = 0;
    public float $destinationLat = 0;
    public float $destinationLon = 0;
    public string $eta = '5 min';

    public function mount(string $rideId): void
    {
        $this->rideId = $rideId;
        $this->loadRideInfo();
    }

    public function loadRideInfo(): void
    {
        // In real app, fetch from database and real-time location service
        $this->driverName = 'Иван';
        $this->vehicleLicense = 'А123БВ77';
        $this->driverLat = 55.7558;
        $this->driverLon = 37.6173;
    }

    public function callDriver(): void
    {
        $this->dispatch('call-driver', rideId: $this->rideId);
    }

    public function render(): View
    {
        return view('livewire.auto.taxi-ride-tracker');
    }
}
