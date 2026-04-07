<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Events\Services;

use App\Domains\EventPlanning\Events\Models\EventTicket;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EventsService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'events:ticket:';
    private const RATE_LIMIT_MAX = 20;
    private const RATE_LIMIT_DECAY = 3600;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать билет на мероприятие.
     */
    public function createTicket(
        int $eventId,
        int $priceKopecks,
        string $correlationId = '',
    ): EventTicket {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'event_ticket',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($eventId, $priceKopecks, $correlationId, $userId): EventTicket {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $ticket = EventTicket::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'event_id' => $eventId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'ticket_code' => strtoupper(Str::random(12)),
                'tags' => ['event' => true],
            ]);

            $this->audit->log(
                action: 'event_ticket_created',
                subjectType: EventTicket::class,
                subjectId: $ticket->id,
                old: [],
                new: $ticket->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Event ticket created', [
                'ticket_id' => $ticket->id,
                'correlation_id' => $correlationId,
            ]);

            return $ticket;
        });
    }

    /**
     * Завершить оплату и выплатить организатору.
     */
    public function completeTicket(int $ticketId, string $correlationId = ''): EventTicket
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($ticketId, $correlationId): EventTicket {
            $ticket = EventTicket::findOrFail($ticketId);

            if ($ticket->payment_status !== 'completed') {
                throw new \RuntimeException('Ticket payment not completed', 400);
            }

            $ticket->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $ticket->tenant_id,
                amount: $ticket->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['ticket_id' => $ticket->id],
            );

            $this->audit->log(
                action: 'event_ticket_completed',
                subjectType: EventTicket::class,
                subjectId: $ticket->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $ticket;
        });
    }

    /**
     * Отменить билет и вернуть оплату.
     */
    public function cancelTicket(int $ticketId, string $correlationId = ''): EventTicket
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($ticketId, $correlationId): EventTicket {
            $ticket = EventTicket::findOrFail($ticketId);

            if ($ticket->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed ticket', 400);
            }

            $previousStatus = $ticket->payment_status;

            $ticket->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousStatus === 'completed') {
                $this->wallet->credit(
                    walletId: $ticket->tenant_id,
                    amount: $ticket->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['ticket_id' => $ticket->id],
                );
            }

            $this->audit->log(
                action: 'event_ticket_cancelled',
                subjectType: EventTicket::class,
                subjectId: $ticket->id,
                old: ['status' => $previousStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            return $ticket;
        });
    }

    /**
     * Получить билет по идентификатору.
     */
    public function getTicket(int $ticketId): EventTicket
    {
        return EventTicket::findOrFail($ticketId);
    }

    /**
     * Получить список билетов клиента.
     */
    public function getUserTickets(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return EventTicket::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
