<?php

declare(strict_types=1);

namespace App\Domains\Beauty\BeautyServices\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Beauty\BeautyServices\Models\BeautyService;
use App\Domains\Beauty\BeautyServices\Models\BeautyStudio;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * BeautyServicesService — сервис управления записями на услуги красоты.
 *
 * Все суммы в копейках. Без статических фасадов.
 * Идентификатор клиента и tenant передаются явно через параметры.
 */
final readonly class BeautyServicesService
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
     * Создаёт запись на услугу.
     *
     * @throws \RuntimeException При превышении лимита или блокировке фрода.
     */
    public function createAppointment(
        int    $studioId,
        string $serviceType,
        int    $durationMinutes,
        string $appointmentDate,
        int    $clientId,
        string $tenantId,
        string $correlationId = '',
    ): BeautyService {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        $fraudCheck = $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'beauty_appointment', amount: 0, correlationId: $correlationId ?? '');

        if (($fraudCheck['decision'] ?? '') === 'block') {
            throw new \RuntimeException('Операция заблокирована службой безопасности.', 403);
        }

        return $this->db->transaction(function () use (
            $studioId, $serviceType, $durationMinutes,
            $appointmentDate, $clientId, $tenantId, $correlationId,
        ): BeautyService {
            $studio = BeautyStudio::withoutGlobalScopes()->findOrFail($studioId);

            $total   = $studio->calculatePrice($durationMinutes);
            $payout  = (int) ($total * (1 - self::PLATFORM_COMMISSION));

            $appointment = BeautyService::create([
                'uuid'             => Uuid::uuid4()->toString(),
                'tenant_id'        => $tenantId,
                'studio_id'        => $studioId,
                'client_id'        => $clientId,
                'correlation_id'   => $correlationId,
                'status'           => 'pending_payment',
                'total_kopecks'    => $total,
                'payout_kopecks'   => $payout,
                'payment_status'   => 'pending',
                'service_type'     => $serviceType,
                'duration_minutes' => $durationMinutes,
                'appointment_date' => $appointmentDate,
                'tags'             => ['beauty' => true],
            ]);

            $this->auditLogger->info('Beauty appointment created.', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $correlationId,
                'total_kopecks'  => $total,
            ]);

            return $appointment;
        });
    }

    /**
     * Завершает услугу и начисляет выплату в кошелёк тенанта.
     *
     * @throws \RuntimeException Если услуга не оплачена.
     */
    public function completeAppointment(
        int    $appointmentId,
        string $tenantId,
        string $correlationId = '',
    ): BeautyService {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($appointmentId, $tenantId, $correlationId): BeautyService {
            $appointment = BeautyService::withoutGlobalScopes()->findOrFail($appointmentId);

            if (! $appointment->isPaid()) {
                throw new \RuntimeException('Услуга не оплачена — завершение невозможно.', 400);
            }

            $appointment->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $this->walletService->credit(
                $tenantId,
                $appointment->payout_kopecks,
                \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,
                $correlationId,
                null,
                null,
                [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $correlationId,
                ],
            );

            return $appointment;
        });
    }

    /**
     * Отменяет запись и при необходимости выполняет возврат.
     *
     * @throws \RuntimeException Если запись уже завершена.
     */
    public function cancelAppointment(
        int    $appointmentId,
        string $tenantId,
        string $correlationId = '',
    ): BeautyService {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($appointmentId, $tenantId, $correlationId): BeautyService {
            $appointment = BeautyService::withoutGlobalScopes()->findOrFail($appointmentId);

            if (! $appointment->isCancellable()) {
                throw new \RuntimeException('Запись уже завершена — отмена невозможна.', 400);
            }

            $appointment->update([
                'status'         => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($appointment->isPaid()) {
                $this->walletService->credit(
                    $tenantId,
                    $appointment->total_kopecks,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,
                    $correlationId,
                    null,
                    null,
                    [
                        'appointment_id' => $appointment->id,
                        'correlation_id' => $correlationId,
                    ],
                );
            }

            return $appointment;
        });
    }

    /**
     * Возвращает историю записей клиента (последние 10).
     *
     * @return Collection<int, BeautyService>
     */
    public function getClientAppointments(int $clientId): Collection
    {
        return BeautyService::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}

