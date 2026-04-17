<?php declare(strict_types=1);

namespace App\Domains\Education\Listeners;

use App\Domains\Education\Events\PriceUpdatedEvent;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

final readonly class PriceUpdatedListener
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function handle(PriceUpdatedEvent $event): void
    {
        $this->audit->record('education_price_updated_crm_sync', 'PriceUpdatedEvent', $event->courseId, [], [
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->tenantId,
            'course_id' => $event->courseId,
            'original_price' => $event->priceAdjustment->originalPriceKopecks,
            'adjusted_price' => $event->priceAdjustment->adjustedPriceKopecks,
            'discount_percent' => $event->priceAdjustment->discountPercent,
            'is_flash_sale' => $event->priceAdjustment->isFlashSale,
            'valid_until' => $event->priceAdjustment->validUntil,
        ], $event->correlationId);

        Log::channel('audit')->info('Price updated synced to CRM', [
            'correlation_id' => $event->correlationId,
            'course_id' => $event->courseId,
        ]);

        $this->sendToCRM($event);
    }

    private function sendToCRM(PriceUpdatedEvent $event): void
    {
        $crmData = [
            'event' => 'price_updated',
            'course_id' => $event->courseId,
            'tenant_id' => $event->tenantId,
            'business_group_id' => $event->businessGroupId,
            'original_price' => $event->priceAdjustment->originalPriceKopecks,
            'adjusted_price' => $event->priceAdjustment->adjustedPriceKopecks,
            'discount_percent' => $event->priceAdjustment->discountPercent,
            'adjustment_reason' => $event->priceAdjustment->adjustmentReason,
            'is_flash_sale' => $event->priceAdjustment->isFlashSale,
            'valid_until' => $event->priceAdjustment->validUntil,
            'correlation_id' => $event->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];

        $webhookUrl = config('services.crm.webhook_url');

        if ($webhookUrl !== null) {
            try {
                \Illuminate\Support\Facades\Http::timeout(10)->post($webhookUrl, $crmData);
            } catch (\Exception $e) {
                Log::channel('audit')->error('CRM price sync failed', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
