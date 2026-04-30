<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Psr\Log\LoggerInterface;
use Filament\Widgets\Widget;

/**
 * Class TeamPresenceWidget
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class TeamPresenceWidget extends Widget
{
    public array $teamMembers = [];

    public int $onlineCount = 0;

    public int $totalCount = 0;

    protected static string $view = 'filament.widgets.team-presence-widget';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle mount operation.
     *
     * @throws \DomainException
     */
    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        try {
            $tenantId = filament()->getTenant()->id;

            // В реальности здесь должна быть логика получения командных данных
            // из Tenant или BusinessGroup
            $this->teamMembers = [];
            $this->onlineCount = 0;
            $this->totalCount = 0;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to load team presence data', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function refresh(): void
    {
        $this->loadData();
    }
}
