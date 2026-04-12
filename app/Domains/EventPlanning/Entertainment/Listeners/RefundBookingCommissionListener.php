<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class RefundBookingCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    use InteractsWithQueue;
use App\Services\FraudControlService;

        public function handle(BookingCreated $event): void
        {
            if ($event->booking->status !== 'cancelled') {
                return;
            }

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $commissionAmount = (int) ($event->booking->commission_amount * 100);
                    $wallet = \App\Models\Wallet::lockForUpdate()->where('tenant_id', $event->booking->tenant_id)->firstOrFail();
                    $wallet->increment('balance', $commissionAmount);

                    \App\Models\BalanceTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'refund',
                        'amount' => $commissionAmount,
                        'description' => "Booking commission refund #{$event->booking->id}",
                        'correlation_id' => $event->correlationId,
                    ]);

                    $this->logger->info('Booking commission refunded', [
                        'booking_id' => $event->booking->id,
                        'venue_id' => $event->booking->venue_id,
                        'customer_id' => $event->booking->customer_id,
                        'amount' => $event->booking->commission_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to refund booking commission', [
                    'booking_id' => $event->booking->id,
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
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
