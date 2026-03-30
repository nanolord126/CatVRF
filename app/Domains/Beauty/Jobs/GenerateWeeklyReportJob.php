<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GenerateWeeklyReportJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
