<?php declare(strict_types=1);

namespace App\Domains\Taxi\Jobs;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class RideReminderJob
{

    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private TaxiRide $ride,
            private string $correlationId = '', private readonly Request $request, private readonly LoggerInterface $logger) {
            $this->onQueue('notifications');

        }

        public function handle(): void
        {
            try {
                $this->logger->info('Ride reminder job started', [
                    'ride_id' => $this->ride->id,
                    'correlation_id' => $this->correlationId,
                ]);

                // Проверить, что поездка ещё в статусе waiting
                $ride = TaxiRide::query()->find($this->ride->id);
                if (!$ride || $ride->status !== 'waiting') {
                    $this->logger->notice('Ride not in waiting status, skipping reminder', [
                        'ride_id' => $this->ride->id,
                        'status' => $ride?->status,
                        'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);

                    return;
                }
                // Notification::send($ride->passenger, new RideReminderNotification($ride));

                $this->logger->info('Ride reminder sent', [
                    'ride_id' => $ride->id,
                    'passenger_id' => $ride->passenger_id,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Ride reminder job failed', [
                    'ride_id' => $this->ride->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        public function retryUntil(): Carbon
        {
            return now()->addHours(1);
        }
}
