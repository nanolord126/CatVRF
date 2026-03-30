<?php declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductPurchaseCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function handle(PurchaseCreated $event): void
        {
            try {
                Log::channel('audit')->info('Processing purchase commission deduction', [
                    'purchase_id' => $event->purchase->id,
                    'commission_amount' => $event->purchase->commission_amount,
                    'correlation_id' => $event->correlationId,
                ]);

                DB::transaction(function () use ($event) {
                    $wallet = \App\Models\Wallet::lockForUpdate()
                        ->where('tenant_id', $event->purchase->tenant_id)
                        ->firstOrFail();

                    $wallet->decrement('balance', intval($event->purchase->commission_amount * 100));

                    \App\Models\BalanceTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => intval($event->purchase->commission_amount * 100),
                        'status' => 'completed',
                        'correlation_id' => $event->correlationId,
                        'metadata' => [
                            'purchase_id' => $event->purchase->id,
                            'studio_id' => $event->purchase->studio_id,
                        ],
                    ]);
                });

                Log::channel('audit')->info('Purchase commission deducted successfully', [
                    'purchase_id' => $event->purchase->id,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to deduct purchase commission', [
                    'purchase_id' => $event->purchase->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
