<?php declare(strict_types=1);

namespace App\Domains\Taxi\Jobs;

use Illuminate\Notifications\ChannelManager;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final class RideReminderJob implements ShouldQueue
{
    public function __construct(
        private readonly ChannelManager $notificationManager,
        private readonly TaxiRide $ride,
        private readonly string $correlationId = '', private readonly Request $request, private readonly LoggerInterface $logger) {
            $this->onQueue('notifications');
        }

        public function tags(): array
    {
        return ['taxi', 'job'];
    }

    public function handle(): void
        {
            try {
                $this->logger->$this->logger->info('Ride reminder job started', [
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
                // $this->notificationManager->send($ride->passenger, new RideReminderNotification($ride));

                $this->logger->$this->logger->info('Ride reminder sent', [
                    'ride_id' => $ride->id,
                    'passenger_id' => $ride->passenger_id,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Exception $e) {
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
            return CarbonImmutable::now()->addHours(1);
        }


    public function failed(Exception $exception): void
    {
        $this->logger->error('taxi job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
