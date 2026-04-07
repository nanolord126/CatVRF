<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\TechSupport\Services;

use App\Domains\HomeServices\TechSupport\Models\SupportTicket;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * TechSupportService — управление тикетами технической поддержки.
 *
 * Создание, завершение и отмена заявок на техподдержку.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class TechSupportService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать тикет техподдержки.
     */
    public function createTicket(
        int    $specialistId,
        string $issueType,
        int    $supportHours,
        string $dueDate,
        string $correlationId = '',
    ): SupportTicket {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($specialistId, $issueType, $supportHours, $dueDate, $correlationId): SupportTicket {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'tech_support',
                amount: 0,
                correlationId: $correlationId,
            );

            $ratePerHourKopecks = 150000;
            $total = $ratePerHourKopecks * $supportHours;

            $ticket = SupportTicket::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'specialist_id'  => $specialistId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'issue_type'     => $issueType,
                'support_hours'  => $supportHours,
                'due_date'       => $dueDate,
                'tags'           => ['tech' => true],
            ]);

            $this->logger->info('Support ticket created', [
                'ticket_id'      => $ticket->id,
                'correlation_id' => $correlationId,
            ]);

            return $ticket;
        });
    }

    /**
     * Завершить тикет и выплатить специалисту.
     */
    public function completeTicket(int $ticketId, string $correlationId = ''): SupportTicket
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($ticketId, $correlationId): SupportTicket {
            $ticket = SupportTicket::findOrFail($ticketId);

            if ($ticket->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $ticket->update([
                'status'         => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $ticket->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['ticket_id' => $ticket->id],
            );

            $this->logger->info('Support ticket completed', [
                'ticket_id'      => $ticket->id,
                'correlation_id' => $correlationId,
            ]);

            return $ticket;
        });
    }

    /**
     * Отменить тикет и вернуть средства.
     */
    public function cancelTicket(int $ticketId, string $correlationId = ''): SupportTicket
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($ticketId, $correlationId): SupportTicket {
            $ticket = SupportTicket::findOrFail($ticketId);

            if ($ticket->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed ticket', 400);
            }

            $wasPaid = $ticket->payment_status === 'completed';

            $ticket->update([
                'status'         => 'cancelled',
                'payment_status' => $wasPaid ? 'refunded' : $ticket->payment_status,
                'correlation_id' => $correlationId,
            ]);

            if ($wasPaid) {
                $this->wallet->credit(
                    walletId: tenant()->id,
                    amount: $ticket->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['ticket_id' => $ticket->id],
                );
            }

            $this->logger->info('Support ticket cancelled', [
                'ticket_id'      => $ticket->id,
                'refunded'       => $wasPaid,
                'correlation_id' => $correlationId,
            ]);

            return $ticket;
        });
    }

    /**
     * Получить тикет по ID.
     */
    public function getTicket(int $ticketId): SupportTicket
    {
        return SupportTicket::findOrFail($ticketId);
    }

    /**
     * Получить последние тикеты клиента.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SupportTicket>
     */
    public function getUserTickets(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return SupportTicket::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function __toString(): string
    {
        return static::class;
    }

    /** @return array<string, mixed> */
    public function toDebugArray(): array
    {
        return [
            'class'     => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
