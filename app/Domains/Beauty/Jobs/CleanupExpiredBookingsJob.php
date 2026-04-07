<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;


use Carbon\Carbon;
use App\Domains\Beauty\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use App\Services\FraudControlService;

/**
 * CleanupExpiredBookingsJob — снимает статус pending у просроченных бронирований.
 *
 * Запускается каждые 15 минут через Scheduler.
 */
final class CleanupExpiredBookingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    private string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();
    }

    public function handle(
        LoggerInterface $logger,
        \Illuminate\Database\DatabaseManager $db,
    ): void {
        $db->transaction(function () use ($logger): void {
            $count = Appointment::query()
                ->where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subMinutes(15))
                ->update(['status' => 'expired']);

            $logger->info('Expired bookings cleaned.', [
                'count'          => $count,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['beauty', 'job:cleanup-expired-bookings'];
    }
}
