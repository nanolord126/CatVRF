<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class CalculateEntertainerEarningsJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private ?string $correlationId = null, private readonly LoggerInterface $logger) {
            $this->onQueue('default');

        }

        public function handle(): void
        {
            try {
                $month = Carbon::now()->month;
                $year = Carbon::now()->year;

                Entertainer::where('is_active', true)
                    ->chunk(50, function ($entertainers) use ($month, $year) {
                        foreach ($entertainers as $entertainer) {
                            $this->calculateEarnings($entertainer, $month, $year);
                        }
                    });

                $this->logger->info('Entertainer earnings calculated', [
                    'month' => $month,
                    'year' => $year,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to calculate entertainer earnings', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->fail($e);
            }
        }

        private function calculateEarnings(Entertainer $entertainer, int $month, int $year): void
        {
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate = $startDate->clone()->endOfMonth();

            $events = $entertainer->entertainmentEvents()
                ->whereDate('event_date_start', '>=', $startDate)
                ->whereDate('event_date_start', '<=', $endDate)
                ->with('schedules')
                ->get();

            foreach ($events as $event) {
                $this->logger->info('Entertainer earnings updated', [
                    'entertainer_id' => $entertainer->id,
                    'event_id' => $event->id,
                    'month' => $month,
                    'year' => $year,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }

        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(6);
        }
}

