declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Flowers\Listeners;

use App\Domains\Flowers\Events\FlowerOrderPlaced;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * DeductFlowerOrderCommission
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeductFlowerOrderCommission implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly WalletService $walletService,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function handle(FlowerOrderPlaced $event): void
    {
        try {
            $this->db->transaction(function () use ($event) {
                $this->log->channel('audit')->info('Deduct flower order commission', [
                    'order_id' => $event->order->id,
                    'commission_amount' => $event->order->commission_amount,
                    'correlation_id' => $event->correlationId,
                    'shop_id' => $event->order->shop_id,
                ]);

                $this->walletService->debit(
                    tenantId: $event->order->tenant_id,
                    amount: (int)($event->order->commission_amount * 100),
                    reason: "Flower order commission: {$event->order->order_number}",
                    correlationId: $event->correlationId,
                );
            });
        } catch (\Exception $exception) {
            $this->log->channel('audit')->error('Commission deduction failed', [
                'order_id' => $event->order->id,
                'error' => $exception->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $exception;
        }
    }
}
