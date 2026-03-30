<?php declare(strict_types=1);

namespace App\Domains\Tickets\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductTicketSaleCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(TicketSaleCreated $event): void
        {
            try {
                Log::channel('audit')->info('Deducting ticket sale commission', [
                    'ticket_sale_id' => $event->ticketSale->id,
                    'commission_amount' => $event->ticketSale->commission_amount,
                    'correlation_id' => $event->correlationId,
                ]);

                DB::transaction(function () use ($event) {
                    $wallet = \App\Models\Wallet::where('tenant_id', $event->ticketSale->tenant_id)
                        ->where('type', 'organizer')
                        ->lockForUpdate()
                        ->firstOrFail();

                    $wallet->balance -= $event->ticketSale->commission_amount;
                    $wallet->save();

                    Log::channel('audit')->info('Ticket sale commission deducted', [
                        'wallet_id' => $wallet->id,
                        'new_balance' => $wallet->balance,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to deduct ticket sale commission', [
                    'error' => $e->getMessage(),
                    'ticket_sale_id' => $event->ticketSale->id,
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
