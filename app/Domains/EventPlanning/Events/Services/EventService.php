<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Events\Services;


use Carbon\Carbon;
use App\Domains\EventPlanning\Events\Models\Event;
use App\Domains\EventPlanning\Events\Models\Ticket;
use App\Domains\EventPlanning\Events\Models\TicketOrder;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EventService
{
    private const COMMISSION_RATE = 0.14;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Покупка билетов на мероприятие (hold средств).
     */
    public function buyTickets(int $eventId, int $quantity, string $correlationId = ''): TicketOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        return $this->db->transaction(function () use ($eventId, $quantity, $correlationId, $userId): TicketOrder {
            $event = Event::findOrFail($eventId);

            if ($event->available_tickets < $quantity) {
                throw new \RuntimeException('Not enough tickets available', 400);
            }

            $total = $event->ticket_price_kopecks * $quantity;

            $this->fraud->check(
                userId: $userId,
                operationType: 'buy_tickets',
                amount: $total,
                correlationId: $correlationId,
            );

            $payoutKopecks = $total - (int) ($total * self::COMMISSION_RATE);

            $order = TicketOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'event_id' => $eventId,
                'buyer_id' => $userId,
                'correlation_id' => $correlationId,
                'quantity' => $quantity,
                'total_kopecks' => $total,
                'payout_kopecks' => $payoutKopecks,
                'status' => 'hold',
                'payment_status' => 'hold',
                'tags' => ['event_tickets' => true],
            ]);

            $event->decrement('available_tickets', $quantity);

            for ($i = 0; $i < $quantity; $i++) {
                Ticket::create([
                    'uuid' => (string) Str::uuid(),
                    'ticket_order_id' => $order->id,
                    'event_id' => $eventId,
                    'holder_id' => $userId,
                    'ticket_code' => strtoupper(Str::random(12)),
                    'status' => 'valid',
                    'correlation_id' => $correlationId,
                ]);
            }

            $this->wallet->hold(
                walletId: $userId,
                amount: $total,
                correlationId: $correlationId,
                metadata: ['order_id' => $order->id],
            );

            $this->audit->log(
                action: 'tickets_purchased',
                subjectType: TicketOrder::class,
                subjectId: $order->id,
                old: [],
                new: ['quantity' => $quantity, 'total' => $total],
                correlationId: $correlationId,
            );

            $this->logger->info('Tickets purchased', [
                'order_id' => $order->id,
                'event_id' => $eventId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Чекин посетителя по коду билета.
     */
    public function checkIn(string $ticketCode, string $correlationId = ''): Ticket
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($ticketCode, $correlationId): Ticket {
            $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();

            if ($ticket->status !== 'valid') {
                throw new \RuntimeException('Ticket already used or invalidated', 400);
            }

            $ticket->update([
                'status' => 'checked_in',
                'checked_in_at' => Carbon::now(),
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                action: 'ticket_checked_in',
                subjectType: Ticket::class,
                subjectId: $ticket->id,
                old: ['status' => 'valid'],
                new: ['status' => 'checked_in'],
                correlationId: $correlationId,
            );

            return $ticket;
        });
    }

    /**
     * Финальный расчёт по завершённому мероприятию.
     */
    public function settleEventPayouts(int $eventId, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->db->transaction(function () use ($eventId, $correlationId): void {
            $event = Event::findOrFail($eventId);

            $orders = TicketOrder::where('event_id', $eventId)
                ->where('status', 'hold')
                ->get();

            foreach ($orders as $order) {
                $order->update([
                    'status' => 'completed',
                    'payment_status' => 'completed',
                    'correlation_id' => $correlationId,
                ]);

                $this->wallet->credit(
                    walletId: $event->organizer_id,
                    amount: $order->payout_kopecks,
                    type: BalanceTransactionType::PAYOUT,
                    correlationId: $correlationId,
                    metadata: ['order_id' => $order->id, 'event_id' => $eventId],
                );
            }

            $event->update(['status' => 'settled']);

            $this->audit->log(
                action: 'event_settled',
                subjectType: Event::class,
                subjectId: $eventId,
                old: ['status' => $event->status],
                new: ['status' => 'settled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Event payouts settled', [
                'event_id' => $eventId,
                'orders_settled' => $orders->count(),
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
