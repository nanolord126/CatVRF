<?php declare(strict_types=1);

namespace App\Domains\Fashion\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class DynamicPriceAppliedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $productId,
        public int $tenantId,
        public ?int $businessGroupId,
        public float $basePrice,
        public float $dynamicPrice,
        public float $discountPercent,
        public float $trendScore,
        public bool $isFlashSale,
        public ?Carbon $flashSaleEndTime,
        public string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('fashion.products.' . $this->productId),
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'fashion.dynamic.price.applied';
    }
}
