<?php declare(strict_types=1);

namespace App\Domains\Tickets\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class RefundTicketSaleCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(TicketSaleRefunded $event): void
        {
            try {
                $this->logger->info('Refunding ticket sale commission', [
                    'ticket_sale_id' => $event->ticketSale->id,
                    'commission_amount' => $event->ticketSale->commission_amount,
                    'reason' => $event->reason,
                    'correlation_id' => $event->correlationId,
                ]);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $this->db->transaction(function () use ($event) {
                    $wallet = \App\Models\Wallet::where('tenant_id', $event->ticketSale->tenant_id)
                        ->where('type', 'organizer')
                        ->lockForUpdate()
                        ->firstOrFail();

                    $wallet->balance += $event->ticketSale->commission_amount;
                    $wallet->save();

                    $this->logger->info('Ticket sale commission refunded', [
                        'wallet_id' => $wallet->id,
                        'new_balance' => $wallet->balance,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to refund ticket sale commission', [
                    'error' => $e->getMessage(),
                    'ticket_sale_id' => $event->ticketSale->id,
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
