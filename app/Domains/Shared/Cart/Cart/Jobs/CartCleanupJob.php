<?php

declare(strict_types=1);

namespace App\Domains\Cart\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Domains\Cart\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CartCleanupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function onQueue(): string
    {
        return 'default';
    }

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
    ,
        public readonly string $correlationId = '') {}

    public function handle(): void
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $expired = Cart::expired()
            ->active()
            ->get();

        foreach ($expired as $cart) {
            $cart->update(['status' => 'expired', 'reserved_until' => null]);
        }

        $this->log->channel('audit')->$this->logger->info('Cart cleanup completed', [
            'expired_count' => $expired->count(),
        ]);
    }
}
