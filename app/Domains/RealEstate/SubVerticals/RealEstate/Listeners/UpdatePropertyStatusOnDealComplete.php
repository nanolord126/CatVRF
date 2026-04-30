<?php

declare(strict_types=1);

namespace Modules\RealEstate\Listeners;

use Modules\RealEstate\Events\DealCompleted;
use Illuminate\Log\LogManager;

final class UpdatePropertyStatusOnDealComplete
{
    public function __construct(
        private readonly LogManager $log,
    ) {}

    public function handle(DealCompleted $event): void
    {
        $property = $event->property;

        try {
            $property->markAsSold();

            $this->log->channel('audit')->info('real_estate.property.status.updated.on_deal_complete', [
                'property_id' => $property->id,
                'booking_id' => $event->booking->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->critical('real_estate.property.status.update.failed', [
                'property_id' => $property->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
