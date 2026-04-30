<?php

declare(strict_types=1);

namespace Modules\Loyalty\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Application\Services\LoyaltyService;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;

final readonly class ProcessLoyaltyPoints implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private int $guestId,
        private string $programId,
        private float $orderAmount,
        private ?string $tierSlug,
        private ?string $sourceType,
        private ?int $sourceId,
        private ?string $correlationId
    ) {
        $this->onQueue(config('loyalty.queue.queue_name', 'loyalty'));
    }

    public function handle(LoyaltyService $loyaltyService): void
    {
        try {
            $loyaltyService->processOrderLoyalty(
                guestId: $this->guestId,
                programId: $this->programId,
                orderAmount: CurrencyAmount::fromFloat($this->orderAmount),
                tierSlug: $this->tierSlug,
                sourceType: $this->sourceType,
                sourceId: $this->sourceId
            );

            Log::info('Loyalty points processed successfully', [
                'guest_id' => $this->guestId,
                'program_id' => $this->programId,
                'order_amount' => $this->orderAmount,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process loyalty points', [
                'guest_id' => $this->guestId,
                'program_id' => $this->programId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->release(60); // Retry after 60 seconds
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Loyalty points processing failed permanently', [
            'guest_id' => $this->guestId,
            'program_id' => $this->programId,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
