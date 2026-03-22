<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Tickets\Models\{TicketSale, Event, TicketType};
use App\Domains\Tickets\Events\TicketSaleCreated;
use Illuminate\Support\Facades\DB;
use Throwable;

final class TicketSalesService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createSale(
        int $eventId,
        int $ticketTypeId,
        int $quantity,
        int $buyerId,
        string $correlationId = '',
    ): TicketSale {


        try {
            Log::channel('audit')->info('Creating ticket sale', [
                'event_id' => $eventId,
                'ticket_type_id' => $ticketTypeId,
                'quantity' => $quantity,
                'buyer_id' => $buyerId,
                'correlation_id' => $correlationId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );

            $sale = DB::transaction(function () use ($eventId, $ticketTypeId, $quantity, $buyerId, $correlationId) {
                $event = Event::findOrFail($eventId);
                $ticketType = TicketType::findOrFail($ticketTypeId);

                if ($ticketType->getAvailableCount() < $quantity) {
                    throw new \Exception('Not enough tickets available');
                }

                $unitPrice = $ticketType->price;
                $subtotal = (int) ($unitPrice * $quantity * 100);
                $commission = (int) ($subtotal * 14 / 100);
                $totalAmount = $subtotal + $commission;

                $sale = TicketSale::create([
                    'tenant_id' => tenant('id'),
                    'event_id' => $eventId,
                    'buyer_id' => $buyerId,
                    'organizer_id' => $event->organizer_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal / 100,
                    'commission_amount' => $commission / 100,
                    'total_amount' => $totalAmount / 100,
                    'payment_status' => 'pending',
                    'sale_status' => 'completed',
                    'correlation_id' => $correlationId,
                ]);

                $ticketType->increment('sold_quantity', $quantity);
                $event->increment('tickets_sold', $quantity);

                TicketSaleCreated::dispatch($sale, $correlationId);

                return $sale;
            });

            Log::channel('audit')->info('Ticket sale created', [
                'sale_id' => $sale->id,
                'correlation_id' => $correlationId,
            ]);

            return $sale;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create ticket sale', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function confirmPayment(TicketSale $sale, string $transactionId, string $correlationId = ''): TicketSale
    {


        try {
            Log::channel('audit')->info('Confirming ticket sale payment', [
                'sale_id' => $sale->id,
                'correlation_id' => $correlationId,
            ]);

            $sale->update([
                'payment_status' => 'paid',
                'transaction_id' => $transactionId,
                'paid_at' => now(),
            ]);

            Log::channel('audit')->info('Ticket sale payment confirmed', [
                'sale_id' => $sale->id,
                'correlation_id' => $correlationId,
            ]);

            return $sale;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to confirm payment', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function refundSale(TicketSale $sale, string $reason = '', string $correlationId = ''): bool
    {


        try {
            Log::channel('audit')->info('Refunding ticket sale', [
                'sale_id' => $sale->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
DB::transaction(function () use ($sale) {
                $sale->update([
                    'sale_status' => 'refunded',
                    'refunded_at' => now(),
                ]);

                TicketType::find($sale->event->ticketTypes()->first()->id)
                    ?->decrement('sold_quantity', $sale->quantity);
            });

            Log::channel('audit')->info('Ticket sale refunded', [
                'sale_id' => $sale->id,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to refund ticket sale', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
