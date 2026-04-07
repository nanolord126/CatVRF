<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Babysitting\Services;

use App\Domains\HomeServices\Babysitting\Models\BabysittingSession;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * BabysittingService — управление сессиями бэбиситтинга.
 *
 * Бронирование нянь, расчёт стоимости по часам, завершение и отмена.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class BabysittingService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Забронировать сессию бэбиситтинга.
     */
    public function createSession(
        int    $sitterId,
        string $sessionDate,
        int    $durationHours,
        array  $kidsAges,
        string $correlationId = '',
    ): BabysittingSession {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($sitterId, $sessionDate, $durationHours, $kidsAges, $correlationId): BabysittingSession {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'babysitting',
                amount: 0,
                correlationId: $correlationId,
            );

            $ratePerHourKopecks = 80000;
            $total = $ratePerHourKopecks * $durationHours;

            $session = BabysittingSession::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'sitter_id'      => $sitterId,
                'parent_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'session_date'   => $sessionDate,
                'duration_hours' => $durationHours,
                'kids_ages'      => $kidsAges,
                'tags'           => ['babysitting' => true],
            ]);

            $this->logger->info('Babysitting session booked', [
                'session_id'     => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Завершить сессию и выплатить няне.
     */
    public function completeSession(int $sessionId, string $correlationId = ''): BabysittingSession
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): BabysittingSession {
            $session = BabysittingSession::findOrFail($sessionId);

            if ($session->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $session->update([
                'status'         => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $session->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['session_id' => $session->id],
            );

            $this->logger->info('Babysitting session completed', [
                'session_id'     => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Отменить сессию и вернуть средства.
     */
    public function cancelSession(int $sessionId, string $correlationId = ''): BabysittingSession
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): BabysittingSession {
            $session = BabysittingSession::findOrFail($sessionId);

            if ($session->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed session', 400);
            }

            $wasPaid = $session->payment_status === 'completed';

            $session->update([
                'status'         => 'cancelled',
                'payment_status' => $wasPaid ? 'refunded' : $session->payment_status,
                'correlation_id' => $correlationId,
            ]);

            if ($wasPaid) {
                $this->wallet->credit(
                    walletId: tenant()->id,
                    amount: $session->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['session_id' => $session->id],
                );
            }

            $this->logger->info('Babysitting session cancelled', [
                'session_id'     => $session->id,
                'refunded'       => $wasPaid,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Получить сессию по ID.
     */
    public function getSession(int $sessionId): BabysittingSession
    {
        return BabysittingSession::findOrFail($sessionId);
    }

    /**
     * Получить последние сессии родителя.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BabysittingSession>
     */
    public function getUserSessions(int $parentId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return BabysittingSession::where('parent_id', $parentId)
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
