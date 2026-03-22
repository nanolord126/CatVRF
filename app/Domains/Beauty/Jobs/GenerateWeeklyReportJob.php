<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\BeautySalon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GenerateWeeklyReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $salonId,
        private readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        $salon = BeautySalon::with(['appointments', 'masters'])
            ->findOrFail($this->salonId);

        $report = [
            'total_appointments' => $salon->appointments()->count(),
            'completed' => $salon->appointments()->where('status', 'completed')->count(),
            'revenue' => $salon->appointments()->sum('price'),
        ];

        Log::channel('audit')->info('Weekly report generated', [
            'salon_id' => $salon->id,
            'report' => $report,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
