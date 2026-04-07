<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Appointment;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * ProcessAppointmentPaymentJob — асинхронная обработка оплаты по завершению записи.
 *
 * CANON 2026:
 * - FraudControlService::check() перед финансовой операцией
 * - WalletService::debit() для списания
 * - WalletService::credit() для начисления мастеру/салону
 * - correlation_id сквозной
 * - AuditService для логирования
 *
 * Запускается из HandleAppointmentCompletedListener.
 */
final class ProcessAppointmentPaymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Максимальное количество попыток.
     */
    public int $tries = 3;

    /**
     * Таймаут (секунды).
     */
    public int $timeout = 30;

    /**
     * Интервал между попытками (секунды).
     */
    public int $backoff = 10;

    public function __construct(
        private int $appointmentId,
        private string $correlationId,
    ) {
    }

    /**
     * Обработка оплаты: списание с клиента → начисление салону (минус комиссия).
     */
    public function handle(
        WalletService $walletService,
        FraudControlService $fraud,
        AuditService $audit,
        LoggerInterface $logger,
        \Illuminate\Database\DatabaseManager $db,
    ): void {
        $appointment = Appointment::with(['user', 'salon'])
            ->findOrFail($this->appointmentId);

        $logger->info('ProcessAppointmentPaymentJob started', [
            'appointment_id' => $this->appointmentId,
            'price_kopecks' => $appointment->price,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $appointment->tenant_id,
        ]);

        $fraud->check(
            userId: $appointment->user_id,
            operationType: 'beauty_appointment_payment',
            amount: (int) $appointment->price,
            correlationId: $this->correlationId,
        );

        $db->transaction(function () use ($appointment, $walletService, $audit, $logger): void {
            $clientWalletId = $this->resolveClientWalletId($appointment);
            $salonWalletId = $this->resolveSalonWalletId($appointment);
            $priceKopecks = (int) $appointment->price;
            $commissionRate = $this->getCommissionRate($appointment);
            $commissionKopecks = (int) round($priceKopecks * $commissionRate);
            $payoutKopecks = $priceKopecks - $commissionKopecks;

            // 1. Списание с клиента
            $walletService->debit(
                $clientWalletId,
                $priceKopecks,
                \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL,
                $this->correlationId,
                null,
                null,
                ['appointment_id' => $appointment->id, 'type' => 'beauty_payment'],
            );

            // 2. Начисление салону (за вычетом комиссии)
            $walletService->credit(
                $salonWalletId,
                $payoutKopecks,
                \App\Domains\Wallet\Enums\BalanceTransactionType::DEPOSIT,
                $this->correlationId,
                null,
                null,
                ['appointment_id' => $appointment->id, 'type' => 'beauty_payout'],
            );

            // 3. Обновляем статус оплаты
            $appointment->update([
                'payment_status' => 'captured',
                'correlation_id' => $this->correlationId,
            ]);

            // 4. Аудит
            $audit->record(
                action: 'beauty_appointment_paid',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: ['payment_status' => 'pending'],
                newValues: [
                    'payment_status' => 'captured',
                    'price_kopecks' => $priceKopecks,
                    'commission_kopecks' => $commissionKopecks,
                    'payout_kopecks' => $payoutKopecks,
                ],
                correlationId: $this->correlationId,
            );

            $logger->info('ProcessAppointmentPaymentJob completed', [
                'appointment_id' => $appointment->id,
                'price_kopecks' => $priceKopecks,
                'commission_kopecks' => $commissionKopecks,
                'payout_kopecks' => $payoutKopecks,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }

    /**
     * Обработка ошибки задания.
     */
    public function failed(\Throwable $exception): void
    {
        report($exception);

        report(new \RuntimeException(
            sprintf(
                'ProcessAppointmentPaymentJob FAILED [appointment_id=%d, correlation_id=%s]: %s',
                $this->appointmentId,
                $this->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }

    /**
     * Получить ID кошелька клиента.
     */
    private function resolveClientWalletId(Appointment $appointment): int
    {
        $wallet = $appointment->user?->wallet;

        if ($wallet === null) {
            throw new \RuntimeException(
                "Client wallet not found for user_id={$appointment->user_id}, appointment_id={$appointment->id}"
            );
        }

        return (int) $wallet->id;
    }

    /**
     * Получить ID кошелька салона (tenant wallet).
     */
    private function resolveSalonWalletId(Appointment $appointment): int
    {
        $salon = $appointment->salon;

        if ($salon === null) {
            throw new \RuntimeException(
                "Salon not found for appointment_id={$appointment->id}"
            );
        }

        // Кошелёк салона привязан к tenant
        $tenantWallet = \App\Domains\Wallet\Models\Wallet::where('tenant_id', $salon->tenant_id)
            ->first();

        if ($tenantWallet === null) {
            throw new \RuntimeException(
                "Tenant wallet not found for tenant_id={$salon->tenant_id}"
            );
        }

        return (int) $tenantWallet->id;
    }

    /**
     * Комиссия платформы: B2C — 14%, B2B — зависит от tier.
     */
    private function getCommissionRate(Appointment $appointment): float
    {
        if ($appointment->business_group_id !== null) {
            return 0.10; // B2B — 10%
        }

        return 0.14; // B2C — 14%
    }
}
