<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Listeners;

use Modules\Flowers\Domain\Events\OrderStatusChanged;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Modules\Flowers\Domain\Repositories\ClientRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class UpdateClientLoyalty implements ShouldQueue
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->order->status !== OrderStatus::DELIVERED && $event->order->status !== OrderStatus::PICKED_UP) {
            return;
        }

        $client = $this->clientRepository->findById($event->order->clientId);
        
        if (!$client) {
            return;
        }

        $client = $client->addOrder($event->order->totalAmount);
        $this->clientRepository->save($client);

        Log::info('Updated client loyalty', [
            'client_id' => $client->id,
            'total_orders' => $client->totalOrders,
            'loyalty_tier' => $client->loyaltyTier,
            'loyalty_points' => $client->loyaltyPoints,
        ]);
    }
}
