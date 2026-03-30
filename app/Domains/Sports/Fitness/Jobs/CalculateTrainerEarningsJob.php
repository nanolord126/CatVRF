<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CalculateTrainerEarningsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(public ?string $correlationId = null)
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

                Log::channel('audit')->info('Trainer earnings calculated', [
                    'month' => $month,
                    'year' => $year,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to calculate trainer earnings', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->fail($e);
            }
        }

        private function calculateEarnings(Trainer $trainer, int $month, int $year): void
        {
            try {
                DB::transaction(function () use ($trainer, $month, $year) {
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

                    Log::channel('audit')->info('Trainer earnings updated', [
                        'trainer_id' => $trainer->id,
                        'month' => $month,
                        'year' => $year,
                        'correlation_id' => $this->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to calculate earnings for trainer', [
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
