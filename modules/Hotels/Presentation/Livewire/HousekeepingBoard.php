<?php

declare(strict_types=1);

namespace Modules\Hotels\Http\Livewire;

use Carbon\CarbonImmutable;
use Livewire\Component;
use Modules\Hotels\Application\Services\HousekeepingService;

final class HousekeepingBoard extends Component
{
    public int $venueId;
    public CarbonImmutable $date;
    public array $boardData = [];
    public array $overdueTasks = [];

    public function mount(int $venueId, ?string $date = null): void
    {
        $this->venueId = $venueId;
        $this->date = $date ? CarbonImmutable::parse($date) : CarbonImmutable::now();
        $this->loadData();
    }

    public function loadData(): void
    {
        $housekeepingService = app(HousekeepingService::class);
        
        $this->boardData = $housekeepingService->getBoardData($this->venueId, $this->date);
        $this->overdueTasks = $housekeepingService->getOverdueTasks($this->venueId);
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

    public function startTask(int $taskId): void
    {
        app(HousekeepingService::class)->startTask($taskId, auth()->id());
        $this->loadData();
    }

    public function completeTask(int $taskId): void
    {
        app(HousekeepingService::class)->completeTask($taskId, auth()->id());
        $this->loadData();
    }

    public function render()
    {
        return view('hotels::livewire.housekeeping-board');
    }
}
