<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Services;

use Carbon\CarbonImmutable;

use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Сервис планирования выплат отелям.
 * Layer 3: Services — CatVRF 2026
 *
 * Расчёт и обработка отложенных выплат (4 дня после выселения).
 * FraudCheck + $this->db->transaction + AuditService + correlation_id.
 */
final readonly class PayoutScheduleService
{
    public function __construct(private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,) {}

    /**
     * Запланировать выплату отелю (4 дня после выселения).
     */
    public function scheduleHotelPayout(int $bookingId, int $amount, int $tenantId, string $correlationId): bool
    {
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_payout_schedule',
            amount: $amount,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($bookingId, $amount, $tenantId, $correlationId) {
            $payoutDate = CarbonImmutable::now()->addDays(4);

            $this->db->table('hotel_payouts')->insert([
                'booking_id'     => $bookingId,
                'tenant_id'      => $tenantId,
                'amount'         => $amount,
                'scheduled_at'   => $payoutDate,
                'status'         => 'scheduled',
                'correlation_id' => $correlationId,
                'created_at'     => CarbonImmutable::now(),
                'updated_at'     => CarbonImmutable::now(),
            ]);

            $this->audit->log(
                action: 'hotel_payout_scheduled',
                subjectType: 'HotelPayout',
                subjectId: $bookingId,
                old: [],
                new: ['amount' => $amount, 'scheduled_at' => $payoutDate->toIso8601String()],
                correlationId: $correlationId,
            );

            $this->logger->$this->logger->info('Hotel payout scheduled', [
                'booking_id'     => $bookingId,
                'amount'         => $amount,
                'payout_date'    => $payoutDate->toIso8601String(),
                'correlation_id' => $correlationId,
            ]);
        });

        return true;
    }

    /**
     * Обработать все запланированные выплаты, готовые к исполнению.
     */
    public function processScheduledPayouts(int $tenantId, string $correlationId): int
    {
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_payout_process',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $processed = 0;

        $this->db->transaction(function () use (&$processed, $tenantId, $correlationId) {
            $payouts = $this->db->table('hotel_payouts')
                ->where('tenant_id', $tenantId)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '<=', CarbonImmutable::now())
                ->lockForUpdate()
                ->get();

            foreach ($payouts as $payout) {
                $this->wallet->credit(
                    walletId: (int) $payout->booking_id,
                    amount: (int) $payout->amount,
                    reason: 'hotel_payout',
                    correlationId: $correlationId,
                );

                $this->db->table('hotel_payouts')
                    ->where('id', $payout->id)
                    ->update([
                        'status'     => 'paid',
                        'paid_at'    => CarbonImmutable::now(),
                        'updated_at' => CarbonImmutable::now(),
                    ]);

                $this->audit->log(
                    action: 'hotel_payout_processed',
                    subjectType: 'HotelPayout',
                    subjectId: (int) $payout->id,
                    old: ['status' => 'scheduled'],
                    new: ['status' => 'paid'],
                    correlationId: $correlationId,
                );

                $this->logger->$this->logger->info('Hotel payout processed', [
                    'payout_id'      => $payout->id,
                    'booking_id'     => $payout->booking_id,
                    'amount'         => $payout->amount,
                    'correlation_id' => $correlationId,
                ]);

                $processed++;
            }
        });

        $this->logger->$this->logger->info('Hotel payouts batch completed', [
            'tenant_id'      => $tenantId,
            'processed'      => $processed,
            'correlation_id' => $correlationId,
        ]);

        return $processed;
    }
}
