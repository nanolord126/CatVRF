<?php

declare(strict_types=1);

namespace Modules\Flowers\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Flowers\Application\Services\FreshnessService;
use Modules\Flowers\Domain\Enums\FreshnessStatus;

final class FreshnessMonitor extends Component
{
    public int $venueId;
    public string $tab = 'overview'; // overview, expiring, low_stock, expired
    public bool $autoRefresh = true;
    public int $refreshInterval = 60; // seconds

    public function mount(int $venueId): void
    {
        $this->venueId = $venueId;
    }

    public function refresh(): void
    {
        $this->freshnessService->updateFreshnessForVenue($this->venueId);
    }

    public function getReportProperty(): array
    {
        return $this->freshnessService->getFreshnessReport($this->venueId);
    }

    public function getExpiringSoonProperty(): array
    {
        return $this->freshnessService->getExpiringSoon($this->venueId, 3);
    }

    public function getLowStockProperty(): array
    {
        return $this->freshnessService->getLowStock($this->venueId);
    }

    public function getExpiredProperty(): array
    {
        return $this->freshnessService->getExpired($this->venueId);
    }

    public function getRecommendedActionsProperty(): array
    {
        return $this->freshnessService->getRecommendedActions($this->venueId);
    }

    public function markAsExpired(int $flowerId): void
    {
        $this->freshnessService->markAsExpired($flowerId);
        $this->dispatch('freshness-updated');
    }

    public function render()
    {
        return view('flowers::livewire.freshness-monitor', [
            'report' => $this->report,
            'expiringSoon' => $this->expiringSoon,
            'lowStock' => $this->lowStock,
            'expired' => $this->expired,
            'recommendedActions' => $this->recommendedActions,
        ]);
    }
}
