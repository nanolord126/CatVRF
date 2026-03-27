<?php declare(strict_types=1);

namespace App\Domains\Travel\Jobs;

use App\Domains\Travel\Models\TravelAgency;
use App\Models\BalanceTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CalculateAgencyEarningsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;

    public function __construct(
        public ?int $agencyId = null,
        public ?string $period = 'monthly',
        
    ) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $agency = TravelAgency::findOrFail($this->agencyId);

                $startDate = match ($this->period) {
                    'daily' => now()->startOfDay(),
                    'weekly' => now()->startOfWeek(),
                    'monthly' => now()->startOfMonth(),
                    default => now()->startOfMonth(),
                };

                $endDate = match ($this->period) {
                    'daily' => now()->endOfDay(),
                    'weekly' => now()->endOfWeek(),
                    'monthly' => now()->endOfMonth(),
                    default => now()->endOfMonth(),
                };

                $totalCommission = $agency->bookings()
                    ->whereBetween('booked_at', [$startDate, $endDate])
                    ->where('status', '!=', 'cancelled')
                    ->sum('commission_amount');

                $totalTourRevenue = $agency->tours()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_amount');

                Log::channel('audit')->info('Agency earnings calculated', [
                    'agency_id' => $this->agencyId,
                    'agency_name' => $agency->name,
                    'period' => $this->period,
                    'total_commission' => $totalCommission,
                    'total_revenue' => $totalTourRevenue,
                    'timestamp' => now(),
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Agency earnings calculation failed', [
                'agency_id' => $this->agencyId,
                'period' => $this->period,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function tags(): array
    {
        return ['travel', 'earnings', 'agency'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addDays(7);
    }
}
