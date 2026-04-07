<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\BoardGames\Services;

use App\Domains\HobbyAndCraft\BoardGames\Models\GameSession;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * BoardGamesService — управление игровыми сессиями в антикафе.
 *
 * Бронирование столов, расчёт стоимости по часам, завершение и отмена.
 * Интегрирован с WalletService для выплат и возвратов.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class BoardGamesService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать игровую сессию.
     */
    public function createSession(
        int    $cafeId,
        string $sessionDate,
        int    $durationHours,
        int    $tableNumber,
        string $correlationId = '',
    ): GameSession {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($cafeId, $sessionDate, $durationHours, $tableNumber, $correlationId): GameSession {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'boardgame_session',
                amount: 0,
                correlationId: $correlationId,
            );

            $ratePerHourKopecks = 50000;
            $total = $ratePerHourKopecks * $durationHours;

            $session = GameSession::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'cafe_id'        => $cafeId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'session_date'   => $sessionDate,
                'duration_hours' => $durationHours,
                'table_number'   => $tableNumber,
                'tags'           => ['boardgame' => true],
            ]);

            $this->logger->info('Board game session created', [
                'session_id'     => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Завершить сессию и выплатить антикафе.
     */
    public function completeSession(int $sessionId, string $correlationId = ''): GameSession
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): GameSession {
            $session = GameSession::findOrFail($sessionId);

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

            $this->logger->info('Board game session completed', [
                'session_id'     => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Отменить сессию и вернуть средства при необходимости.
     */
    public function cancelSession(int $sessionId, string $correlationId = ''): GameSession
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): GameSession {
            $session = GameSession::findOrFail($sessionId);

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

            $this->logger->info('Board game session cancelled', [
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
    public function getSession(int $sessionId): GameSession
    {
        return GameSession::findOrFail($sessionId);
    }

    /**
     * Получить последние сессии клиента.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, GameSession>
     */
    public function getUserSessions(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return GameSession::where('client_id', $clientId)
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
