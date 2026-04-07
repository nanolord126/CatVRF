<?php

declare(strict_types=1);

namespace App\Domains\Beauty\MassageTherapy\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Beauty\MassageTherapy\Models\MassageSession;
use App\Domains\Beauty\MassageTherapy\Models\MassageTherapist;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * MassageTherapyService — сервис управления сеансами массажа.
 *
 * Все суммы в копейках. Без статических фасадов.
 * clientId и tenantId передаются явно через параметры.
 */
final readonly class MassageTherapyService
{
    private const PLATFORM_COMMISSION = 0.14;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $walletService,
        private LoggerInterface $auditLogger,
        private \Illuminate\Database\DatabaseManager $db,
        private Guard $guard,
    ) {}

    /**
     * Создаёт сеанс массажа.
     *
     * @throws \RuntimeException При блокировке фрода.
     */
    public function createSession(
        int    $therapistId,
        string $massageType,
        int    $durationMinutes,
        string $sessionDate,
        int    $clientId,
        string $tenantId,
        string $correlationId = '',
    ): MassageSession {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        $fraudCheck = $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'massage_session', amount: 0, correlationId: $correlationId ?? '');

        if (($fraudCheck['decision'] ?? '') === 'block') {
            throw new \RuntimeException('Операция заблокирована службой безопасности.', 403);
        }

        return $this->db->transaction(function () use (
            $therapistId, $massageType, $durationMinutes,
            $sessionDate, $clientId, $tenantId, $correlationId,
        ): MassageSession {
            $therapist = MassageTherapist::withoutGlobalScopes()->findOrFail($therapistId);

            $total  = (int) ($therapist->price_kopecks_per_minute * $durationMinutes);
            $payout = (int) ($total * (1 - self::PLATFORM_COMMISSION));

            $session = MassageSession::create([
                'uuid'             => Uuid::uuid4()->toString(),
                'tenant_id'        => $tenantId,
                'therapist_id'     => $therapistId,
                'client_id'        => $clientId,
                'correlation_id'   => $correlationId,
                'status'           => 'pending_payment',
                'total_kopecks'    => $total,
                'payout_kopecks'   => $payout,
                'payment_status'   => 'pending',
                'massage_type'     => $massageType,
                'duration_minutes' => $durationMinutes,
                'session_date'     => $sessionDate,
                'tags'             => ['massage' => true],
            ]);

            $this->auditLogger->info('Massage session created.', [
                'session_id'     => $session->id,
                'correlation_id' => $correlationId,
                'total_kopecks'  => $total,
            ]);

            return $session;
        });
    }

    /**
     * Завершает сеанс и зачисляет выплату терапевту.
     *
     * @throws \RuntimeException Если сеанс не оплачен.
     */
    public function completeSession(
        int    $sessionId,
        string $tenantId,
        string $correlationId = '',
    ): MassageSession {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($sessionId, $tenantId, $correlationId): MassageSession {
            $session = MassageSession::withoutGlobalScopes()->findOrFail($sessionId);

            if (! $session->isPaid()) {
                throw new \RuntimeException('Сеанс массажа не оплачен — завершение невозможно.', 400);
            }

            $session->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $this->walletService->credit(
                $tenantId,
                $session->payout_kopecks,
                \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,
                $correlationId,
                null,
                null,
                [
                    'session_id'     => $session->id,
                    'correlation_id' => $correlationId,
                ],
            );

            return $session;
        });
    }

    /**
     * Отменяет сеанс с возвратом средств при необходимости.
     *
     * @throws \RuntimeException Если сеанс уже завершён.
     */
    public function cancelSession(
        int    $sessionId,
        string $tenantId,
        string $correlationId = '',
    ): MassageSession {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($sessionId, $tenantId, $correlationId): MassageSession {
            $session = MassageSession::withoutGlobalScopes()->findOrFail($sessionId);

            if (! $session->isCancellable()) {
                throw new \RuntimeException('Сеанс массажа завершён — отмена невозможна.', 400);
            }

            $session->update([
                'status'         => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($session->isPaid()) {
                $this->walletService->credit(
                    $tenantId,
                    $session->total_kopecks,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,
                    $correlationId,
                    null,
                    null,
                    [
                        'session_id'     => $session->id,
                        'correlation_id' => $correlationId,
                    ],
                );
            }

            return $session;
        });
    }

    /**
     * Возвращает историю сеансов клиента (последние 10).
     *
     * @return Collection<int, MassageSession>
     */
    public function getClientSessions(int $clientId): Collection
    {
        return MassageSession::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}

