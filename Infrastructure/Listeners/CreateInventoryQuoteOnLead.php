<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners;

use Modules\CatCRM\Domain\Events\B2BLeadCreated;
use Modules\CatCRM\Application\Services\CRMInventoryIntegrationService;

final class CreateInventoryQuoteOnLead
{
    public function __construct(
        private readonly CRMInventoryIntegrationService $inventoryIntegration,
    ) {}

    public function handle(B2BLeadCreated $event): void
    {
        // Only create quote if lead has budget and category
        if ($event->lead->budget_range && $event->lead->category) {
            // Default items based on vertical and category
            $items = $this->getDefaultItemsForLead($event->lead);
            
            if (!empty($items)) {
                try {
                    $this->inventoryIntegration->createQuoteRequest($event->lead, $items);
                } catch (\Exception $e) {
                    \Log::error('Failed to create inventory quote', [
                        'lead_id' => $event->lead->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function getDefaultItemsForLead($lead): array
    {
        // Return default items based on vertical and category
        // This should be configured per vertical
        return [];
    }
}
