declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Listeners;

use App\Domains\Tickets\Events\TicketSaleRefunded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

final /**
 * RefundTicketSaleCommissionListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class RefundTicketSaleCommissionListener implements ShouldQueue
{
    public function handle(TicketSaleRefunded $event): void
    {
        try {
            $this->log->channel('audit')->info('Refunding ticket sale commission', [
                'ticket_sale_id' => $event->ticketSale->id,
                'commission_amount' => $event->ticketSale->commission_amount,
                'reason' => $event->reason,
                'correlation_id' => $event->correlationId,
            ]);

            $this->db->transaction(function () use ($event) {
                $wallet = \App\Models\Wallet::where('tenant_id', $event->ticketSale->tenant_id)
                    ->where('type', 'organizer')
                    ->lockForUpdate()
                    ->firstOrFail();

                $wallet->balance += $event->ticketSale->commission_amount;
                $wallet->save();

                $this->log->channel('audit')->info('Ticket sale commission refunded', [
                    'wallet_id' => $wallet->id,
                    'new_balance' => $wallet->balance,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to refund ticket sale commission', [
                'error' => $e->getMessage(),
                'ticket_sale_id' => $event->ticketSale->id,
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
