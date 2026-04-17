<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class CalculateTrainerEarningsJob
{


        public function __construct(public ?string $correlationId = null,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger)
        {
            $this->onQueue('default');
        }

        public function tags(): array
        {
            return ['fitness', 'earnings', 'daily'];
        }

        public function handle(): void
        {
            try {
                $month = now()->month;
                $year = now()->year;

                Trainer::where('is_active', true)
                    ->chunk(50, function ($trainers) use ($month, $year) {
                        foreach ($trainers as $trainer) {
                            $this->calculateEarnings($trainer, $month, $year);
                        }
                    });

                $this->logger->info('Trainer earnings calculated', [
                    'month' => $month,
                    'year' => $year,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to calculate trainer earnings', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->fail($e);
            }
        }

        private function calculateEarnings(Trainer $trainer, int $month, int $year): void
        {
            try {
                $this->db->transaction(function () use ($trainer, $month, $year) {
                    $startDate = now()->setMonth($month)->setYear($year)->startOfMonth();
                    $endDate = clone $startDate;
                    $endDate = $endDate->endOfMonth();

                    FitnessClass::where('trainer_id', $trainer->id)
                        ->whereHas('schedules', function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('scheduled_at', [$startDate, $endDate]);
                        })
                        ->update([
                            'trainer_earnings_calculated' => true,
                        ]);

                    $this->logger->info('Trainer earnings updated', [
                        'trainer_id' => $trainer->id,
                        'month' => $month,
                        'year' => $year,
                        'correlation_id' => $this->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to calculate earnings for trainer', [
                    'trainer_id' => $trainer->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }

        public function retryUntil()
        {
            return now()->addHours(6);
        }
}
