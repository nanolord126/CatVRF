<?php

declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Jobs;

use Carbon\CarbonImmutable;

use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;

final class CalculateTrainerEarningsJob
{
    public function __construct(
        public ?string $correlationId,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['fitness', 'earnings', 'daily'];
    }

    public function handle(): void
    {
        try {
            $month = CarbonImmutable::now()->month;
            $year = CarbonImmutable::now()->year;

            Trainer::where('is_active', true)
                ->chunk(50, function ($trainers) use ($month, $year) {
                    foreach ($trainers as $trainer) {
                        $this->calculateEarnings($trainer, $month, $year);
                    }
                });

            $this->logger->$this->logger->info('Trainer earnings calculated', [
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

    public function retryUntil()
    {
        return CarbonImmutable::now()->addHours(6);
    }

    private function calculateEarnings(Trainer $trainer, int $month, int $year): void
    {
        try {
            $this->db->transaction(function () use ($trainer, $month, $year) {
                $startDate = CarbonImmutable::now()->setMonth($month)->setYear($year)->startOfMonth();
                $endDate = clone $startDate;
                $endDate = $endDate->endOfMonth();

                FitnessClass::where('trainer_id', $trainer->id)
                    ->whereHas('schedules', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('scheduled_at', [$startDate, $endDate]);
                    })
                    ->update([
                        'trainer_earnings_calculated' => true,
                    ]);

                $this->logger->$this->logger->info('Trainer earnings updated', [
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
}
