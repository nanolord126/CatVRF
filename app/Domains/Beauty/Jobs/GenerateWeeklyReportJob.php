<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;


use Carbon\Carbon;
use App\Domains\Beauty\Models\BeautySalon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * GenerateWeeklyReportJob — формирует еженедельный отчёт по салону.
 *
 * Запускается каждый понедельник в 07:00 по часовому поясу тенанта.
 */
final class GenerateWeeklyReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 180;

    private string $correlationId;

    public function __construct(
        private int $salonId,
        string               $correlationId = '') {
        $this->correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();
    }

    public function handle(LoggerInterface $logger): void
    {
        $salon = BeautySalon::with(['appointments', 'masters'])->findOrFail($this->salonId);

        $report = [
            'salon_id'            => $salon->id,
            'total_appointments'  => $salon->appointments()->count(),
            'completed'           => $salon->appointments()->where('status', 'completed')->count(),
            'cancelled'           => $salon->appointments()->where('status', 'cancelled')->count(),
            'revenue_kopecks'     => (int) $salon->appointments()->where('status', 'completed')->sum('price_kopecks'),
            'period_start'        => Carbon::now()->startOfWeek()->toDateString(),
            'period_end'          => Carbon::now()->endOfWeek()->toDateString(),
        ];

        $logger->info('Weekly report generated.', [
            'salon_id'       => $salon->id,
            'report'         => $report,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['beauty', 'job:weekly-report', "salon:{$this->salonId}"];
    }
}
