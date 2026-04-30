<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Application\Services;

use Psr\Log\LoggerInterface;

use Illuminate\Log\LogManager;

final readonly class FoodNotificationService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    public function sendOrderConfirmation(
        string $orderId,
        string $userId,
        string $restaurantId,
        float $totalAmount,
    ): void {
        try {
            $this->log->$this->logger->info('Food order confirmation sent', [
                'order_id' => $orderId,
                'user_id' => $userId,
                'restaurant_id' => $restaurantId,
                'total_amount' => $totalAmount,
            ]);
        } catch (\Throwable $e) {
            $this->log->error('Failed to send food order confirmation', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
