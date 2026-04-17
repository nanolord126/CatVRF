<?php declare(strict_types=1);

namespace App\Domains\Cart\Jobs;

use App\Domains\Cart\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CartCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function onQueue(): string
    {
        return 'default';
    }

    public function handle(): void
    {
        $expired = Cart::expired()
            ->active()
            ->get();

        foreach ($expired as $cart) {
            $cart->update(['status' => 'expired', 'reserved_until' => null]);
        }

        Log::channel('audit')->info('Cart cleanup completed', [
            'expired_count' => $expired->count(),
        ]);
    }
}
