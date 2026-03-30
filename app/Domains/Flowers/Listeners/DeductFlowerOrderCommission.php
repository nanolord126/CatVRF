<?php declare(strict_types=1);

namespace App\Domains\Flowers\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductFlowerOrderCommission extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function __construct(
            private readonly WalletService $walletService,
        ) {}

        public function handle(FlowerOrderPlaced $event): void
        {
            try {
                DB::transaction(function () use ($event) {
                    Log::channel('audit')->info('Deduct flower order commission', [
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
                Log::channel('audit')->error('Commission deduction failed', [
                    'order_id' => $event->order->id,
                    'error' => $exception->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $exception;
            }
        }
}
