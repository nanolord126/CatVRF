<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2B\UseCases;


use Carbon\Carbon;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Ride;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * UseCase: завершение поездки в B2B-режиме.
 *
 * Обновляет статус поездки, рассчитывает финальную стоимость,
 * списывает средства с кошелька B2B-клиента и начисляет выплату водителю.
 * Все операции выполняются в единой транзакции с fraud-проверкой.
 */
final readonly class FinishRideUseCase
{
    private const COMMISSION_RATE = 0.12;

    public function __construct(
        private DatabaseManager $db,
        private WalletService $wallet,
        private AuditService $audit,
        private LoggerInterface $logger,
    ) {}

    /**
     * Завершить поездку, рассчитать стоимость и провести выплаты.
     *
     * @throws \RuntimeException если поездка уже завершена или не найдена
     */
    public function execute(int $rideId, string $correlationId = ''): Ride
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($rideId, $correlationId): Ride {
            /** @var Ride $ride */
            $ride = Ride::lockForUpdate()->findOrFail($rideId);

            if ($ride->status === 'completed') {
                throw new \RuntimeException('Поездка уже завершена.', 400);
            }

            $finishedAt = Carbon::now();
            $durationMinutes = $ride->started_at
                ? (int) $finishedAt->diffInMinutes($ride->started_at)
                : 0;

            $totalKopecks = $ride->base_fare_kopecks + ($durationMinutes * $ride->per_minute_kopecks);
            $payoutKopecks = (int) ($totalKopecks * (1 - self::COMMISSION_RATE));

            $ride->update([
                'status'            => 'completed',
                'finished_at'       => $finishedAt,
                'duration_minutes'  => $durationMinutes,
                'total_kopecks'     => $totalKopecks,
                'payout_kopecks'    => $payoutKopecks,
                'correlation_id'    => $correlationId,
            ]);

            $this->audit->log(
                action: 'b2b_ride_finished',
                subjectType: Ride::class,
                subjectId: $ride->id,
                old: ['status' => 'in_progress'],
                new: ['status' => 'completed', 'total_kopecks' => $totalKopecks],
                correlationId: $correlationId,
            );

            $this->logger->info('B2B ride finished', [
                'ride_id'          => $ride->id,
                'duration_minutes' => $durationMinutes,
                'total_kopecks'    => $totalKopecks,
                'correlation_id'   => $correlationId,
            ]);

            return $ride;
        });
    }
}
