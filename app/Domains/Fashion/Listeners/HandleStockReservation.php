<?php declare(strict_types=1);

/**
 * HandleStockReservation — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/handlestockreservation
 */


namespace App\Domains\Fashion\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class HandleStockReservation
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    use InteractsWithQueue;
use App\Services\FraudControlService;

        public function handle(ItemReservedEvent $event): void
        {
            $this->logger->info('Stock reservation listener triggered', [
                'product_id' => $event->product->id,
                'quantity' => $event->quantity,
                'correlation_id' => $event->correlationId,
            ]);

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $product = FashionProduct::lockForUpdate()->find($event->product->id);

                    if ($product->quantity < $event->quantity) {
                        throw new \RuntimeException('Insufficient stock for reservation');
                    }

                    // Увеличение reserve_quantity на 20 мин (логика сброса в Jobs)
                    $product->increment('reserve_quantity', $event->quantity);

                    $this->logger->info('Reservation successful', [
                        'product_id' => $product->id,
                        'new_reserve' => $product->reserve_quantity,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Stock reservation failed in listener', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
