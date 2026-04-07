<?php declare(strict_types=1);

namespace App\Domains\Medical\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductTestOrderCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(TestOrderCreated $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
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

                    $this->logger->info('Medical test order commission deducted', [
                        'test_order_id' => $testOrder->id,
                        'patient_id' => $testOrder->patient_id,
                        'clinic_id' => $testOrder->clinic_id,
                        'commission_amount' => $commission,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to deduct test order commission', [
                    'test_order_id' => $event->testOrder->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
