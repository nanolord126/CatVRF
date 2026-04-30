<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Models\Cart;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * CartCleanupJob — освобождает истёкшие корзины.
 * Канон CatVRF 2026: запускается каждую минуту.
 *
 * Логика:
 *   1. Найти все активные корзины с reserved_until < CarbonImmutable::now()
 *   2. Освободить резервы в инвентаре
 *   3. Пометить корзину как expired
 */
final class CartCleanupJob implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
    ,
        public readonly string $correlationId = '') {}

    public function handle(InventoryService $inventory): void
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $expiredCarts = Cart::where('status', 'active')
            ->where('reserved_until', '<', CarbonImmutable::now())
            ->with('items')
            ->get();

        foreach ($expiredCarts as $cart) {
            foreach ($cart->items as $item) {
                try {
                    $inventory->releaseReserve(
                        $item->product_id,
                        $item->quantity,
                        'cart',
                        $cart->id
    );
                } catch (\Throwable $e) {
                    $this->logger->channel('audit')->error('CartCleanupJob: failed to release reserve', [
                        'cart_id'    => $cart->id,
                        'product_id' => $item->product_id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            $cart->items()->delete();
            $cart->update(['status' => 'expired', 'reserved_until' => null]);

            $this->logger->channel('audit')->$this->logger->info('CartCleanupJob: cart expired', [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
            ]);
        }
    }
}
