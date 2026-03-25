<?php declare(strict_types=1);

namespace App\Domains\Medical\Jobs;

use App\Domains\Medical\Models\MedicalClinic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CalculateClinicEarningsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['medical', 'earnings', 'daily'];
    }

    public function handle(): void
    {
        try {
            $month = now()->month;
            $year = now()->year;

            MedicalClinic::where('is_active', true)->chunk(50, function ($clinics) use ($month, $year) {
                foreach ($clinics as $clinic) {
                    $this->calculateClinicEarnings($clinic, $month, $year);
                }
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to calculate clinic earnings', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }

    private function calculateClinicEarnings(MedicalClinic $clinic, int $month, int $year): void
    {
        try {
            $startDate = now()->setMonth($month)->setYear($year)->startOfMonth();
            $endDate = now()->setMonth($month)->setYear($year)->endOfMonth();

            $appointments = $clinic->appointments()
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->get();

            $totalRevenue = $appointments->sum('price');
            $totalCommission = $appointments->sum('commission_amount');

            $this->log->channel('audit')->info('Monthly clinic earnings calculated', [
                'clinic_id' => $clinic->id,
                'month' => $month,
                'year' => $year,
                'total_revenue' => $totalRevenue,
                'total_commission' => $totalCommission,
                'appointment_count' => $appointments->count(),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to calculate earnings for clinic', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);
    }
}
