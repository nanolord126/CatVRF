<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\Cart;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Log\LogManager;


/**
 * CartCleanupJob — освобождает истёкшие корзины.
 * Канон CatVRF 2026: запускается каждую минуту.
 *
 * Логика:
 *   1. Найти все активные корзины с reserved_until < now()
 *   2. Освободить резервы в инвентаре
 *   3. Пометить корзину как expired
 */
final class CartCleanupJob implements ShouldQueue
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}

    public int $tries = 3;

    public function handle(InventoryService $inventory): void
    {
        $expiredCarts = Cart::where('status', 'active')
            ->where('reserved_until', '<', now())
            ->with('items')
            ->get();

        foreach ($expiredCarts as $cart) {
            foreach ($cart->items as $item) {
                try {
                    $inventory->releaseReserve(
                        $item->product_id,
                        $item->quantity,
                        'cart',
                        $cart->id,
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

            $this->logger->channel('audit')->info('CartCleanupJob: cart expired', [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
            ]);
        }
    }
}
