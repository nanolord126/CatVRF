<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Jobs;

use Modules\Inventory\Domain\Entities\InventoryRequest;
use Modules\CatCRM\Application\Services\CRMInventoryIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessInventoryQuoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $inventoryRequestId,
    ) {}

    public function handle(CRMInventoryIntegrationService $inventoryIntegration): void
    {
        $request = InventoryRequest::findOrFail($this->inventoryRequestId);
        
        try {
            $inventoryIntegration->processQuoteRequest($request);
        } catch (\Exception $e) {
            \Log::error('Failed to process inventory quote', [
                'request_id' => $this->inventoryRequestId,
                'error' => $e->getMessage(),
            ]);
            
            $this->release(60);
        }
    }
}
