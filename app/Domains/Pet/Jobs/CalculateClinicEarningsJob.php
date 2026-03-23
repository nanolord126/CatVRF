<?php declare(strict_types=1);

namespace App\Domains\Pet\Jobs;

use App\Domains\Pet\Models\PetClinic;
use App\Models\BalanceTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CalculateClinicEarningsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $clinicId = 0,
        private readonly ?\DateTime $month = null,
        private readonly string $correlationId = '',
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $clinic = PetClinic::find($this->clinicId);

            if (!$clinic) {
                Log::warning('Pet clinic not found', [
                    'clinic_id' => $this->clinicId,
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            DB::transaction(function () use ($clinic) {
                // Calculate appointment earnings
                $appointmentEarnings = $clinic->appointments()
                    ->whereMonth('created_at', $this->month->month)
                    ->whereYear('created_at', $this->month->year)
                    ->where('payment_status', 'paid')
                    ->sum('commission_amount');

                // Calculate boarding earnings
                $boardingEarnings = $clinic->boardingReservations()
                    ->whereMonth('created_at', $this->month->month)
                    ->whereYear('created_at', $this->month->year)
                    ->where('payment_status', 'paid')
                    ->sum('commission_amount');

                $totalEarnings = $appointmentEarnings + $boardingEarnings;

                BalanceTransaction::create([
                    'tenant_id' => $clinic->tenant_id,
                    'wallet_id' => $clinic->owner->wallet->id,
                    'type' => 'commission',
                    'amount' => (int)($totalEarnings * 100),
                    'status' => 'completed',
                    'reference_type' => 'pet_clinic_earnings',
                    'reference_id' => $clinic->id,
                    'correlation_id' => $this->correlationId,
                    'metadata' => [
                        'month' => $this->month->format('Y-m'),
                        'appointment_earnings' => $appointmentEarnings,
                        'boarding_earnings' => $boardingEarnings,
                        'total_earnings' => $totalEarnings,
                    ],
                ]);

                Log::channel('audit')->info('Pet clinic earnings calculated', [
                    'clinic_id' => $clinic->id,
                    'month' => $this->month->format('Y-m'),
                    'appointment_earnings' => $appointmentEarnings,
                    'boarding_earnings' => $boardingEarnings,
                    'total_earnings' => $totalEarnings,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to calculate clinic earnings', [
                'clinic_id' => $this->clinicId,
                'month' => $this->month->format('Y-m'),
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addDays(7);
    }
}
