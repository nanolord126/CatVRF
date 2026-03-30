<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CalculateEntertainerEarningsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public ?string $correlationId = null,
        ) {
            $this->onQueue('default');

        }

        public function handle(): void
        {
            try {
                $month = now()->month;
                $year = now()->year;

                Entertainer::where('is_active', true)
                    ->chunk(50, function ($entertainers) use ($month, $year) {
                        foreach ($entertainers as $entertainer) {
                            $this->calculateEarnings($entertainer, $month, $year);
                        }
                    });

                Log::channel('audit')->info('Entertainer earnings calculated', [
                    'month' => $month,
                    'year' => $year,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to calculate entertainer earnings', [
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
                Log::channel('audit')->info('Entertainer earnings updated', [
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
            return now()->addHours(6);
        }
}
