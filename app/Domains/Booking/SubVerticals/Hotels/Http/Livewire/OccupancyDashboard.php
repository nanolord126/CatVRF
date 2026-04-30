<?php

declare(strict_types=1);

namespace Modules\Hotels\Http\Livewire;

use Carbon\CarbonImmutable;
use Livewire\Component;
use Modules\Hotels\Application\Services\RevenueService;

final class OccupancyDashboard extends Component
{
    public int $venueId;
    public CarbonImmutable $date;
    public array $stats = [];
    public array $occupancyTrend = [];
    public array $revenueData = [];
    public array $forecast = [];

    public function mount(int $venueId, ?string $date = null): void
    {
        $this->venueId = $venueId;
        $this->date = $date ? CarbonImmutable::parse($date) : CarbonImmutable::now();
        $this->loadData();
    }

    public function loadData(): void
    {
        $revenueService = app(RevenueService::class);
        
        $this->stats = $revenueService->getDashboardStats($this->venueId, $this->date);
        
        // Get 7-day occupancy trend
        $startDate = $this->date->subDays(6);
        $endDate = $this->date;
        $this->occupancyTrend = $revenueService->getOccupancyRateRange(
            $this->venueId,
            $startDate,
            $endDate
        );
        
        // Get 7-day revenue
        $this->revenueData = $revenueService->getRevenueForPeriod(
            $this->venueId,
            $startDate,
            $endDate
        );
        
        // Get 14-day forecast
        $forecastStart = $this->date;
        $forecastEnd = $this->date->addDays(14);
        $this->forecast = $revenueService->getForecast(
            $this->venueId,
            $forecastStart,
            $forecastEnd
        );
    }

    public function previousDay(): void
    {
        $this->date = $this->date->subDay();
        $this->loadData();
    }

    public function nextDay(): void
    {
        $this->date = $this->date->addDay();
        $this->loadData();
    }

    public function today(): void
    {
        $this->date = CarbonImmutable::now();
        $this->loadData();
    }

    public function render()
    {
        return view('hotels::livewire.occupancy-dashboard');
    }
}
