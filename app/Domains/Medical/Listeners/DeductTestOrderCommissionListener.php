<?php declare(strict_types=1);

namespace App\Domains\Medical\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductTestOrderCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(TestOrderCreated $event): void
        {
            try {
                DB::transaction(function () use ($event) {
                    $testOrder = $event->testOrder;
                    $commission = $testOrder->commission_amount;

                    if ($commission <= 0) return;

                    $wallet = \App\Models\Wallet::lockForUpdate()
                        ->where('tenant_id', $testOrder->tenant_id)
                        ->firstOrFail();

                    $wallet->decrement('balance', (int) ($commission * 100));

                    \App\Models\BalanceTransaction::create([
                        'tenant_id' => $testOrder->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => (int) ($commission * 100),
                        'description' => "Commission for test order #{$testOrder->test_order_number}",
                        'correlation_id' => $event->correlationId,
                    ]);

                    Log::channel('audit')->info('Medical test order commission deducted', [
                        'test_order_id' => $testOrder->id,
                        'patient_id' => $testOrder->patient_id,
                        'clinic_id' => $testOrder->clinic_id,
                        'commission_amount' => $commission,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to deduct test order commission', [
                    'test_order_id' => $event->testOrder->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
