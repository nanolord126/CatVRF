<?php

declare(strict_types=1);

namespace App\Livewire\Auto;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

/**
 * Class TaxiRideTracker
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 */
final class TaxiRideTracker extends Component
{
    private readonly string $rideId;

    private readonly string $driverName = '';

    private readonly string $vehicleLicense = '';

    private readonly float $driverLat = 0;

    private readonly float $driverLon = 0;

    private readonly float $destinationLat = 0;

    private readonly float $destinationLon = 0;

    private readonly string $eta = '5 min';

    /**
     * Handle mount operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(string $rideId): void
    {
        $this->rideId = $rideId;
        $this->loadRideInfo();
    }

    /**
     * Handle loadRideInfo operation.
     *
     * @throws \DomainException
     */
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
        return $this->viewFactory->make('livewire.auto.taxi-ride-tracker');
    }
}
