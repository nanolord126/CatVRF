<?php declare(strict_types=1);

namespace App\Domains\Pet\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class CalculateClinicEarningsJob implements ShouldQueue
{
    private readonly string $resolvedCorrelationId;

    public function __construct(
        private readonly int $clinicId = 0,
        private readonly string $periodStart = '',
        private readonly string $periodEnd = '',
        private readonly string $correlationId = '',
    ) {
        $this->resolvedCorrelationId = $this->correlationId !== '' ? $this->correlationId : (string) Str::uuid();
        $this->onQueue('earnings');
    }

    public function handle(LoggerInterface $logger): void
    {
        $logger->info('CalculateClinicEarningsJob: Starting earnings calculation', [
            'clinic_id' => $this->clinicId,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'correlation_id' => $this->resolvedCorrelationId,
        ]);

        try {
            $clinic = PetClinic::findOrFail($this->clinicId);

            $completedAppointments = PetAppointment::where('clinic_id', $this->clinicId)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$this->periodStart, $this->periodEnd])
                ->get();

            $totalRevenue = 0;
            $totalPlatformFee = 0;
            $totalClinicEarnings = 0;
            $appointmentCount = $completedAppointments->count();

            foreach ($completedAppointments as $appointment) {
                $appointmentRevenue = $appointment->total_price_kopecks;
                $platformFee = (int) ($appointmentRevenue * 0.14);
                $clinicShare = $appointmentRevenue - $platformFee;

                $totalRevenue += $appointmentRevenue;
                $totalPlatformFee += $platformFee;
                $totalClinicEarnings += $clinicShare;
            }

            $earningsReport = ClinicEarningsReport::updateOrCreate(
                [
                    'clinic_id' => $this->clinicId,
                    'period_start' => $this->periodStart,
                    'period_end' => $this->periodEnd,
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $clinic->tenant_id,
                    'total_revenue_kopecks' => $totalRevenue,
                    'platform_fee_kopecks' => $totalPlatformFee,
                    'clinic_earnings_kopecks' => $totalClinicEarnings,
                    'appointment_count' => $appointmentCount,
                    'average_ticket_kopecks' => $appointmentCount > 0 ? (int) ($totalRevenue / $appointmentCount) : 0,
                    'correlation_id' => $this->resolvedCorrelationId,
                    'calculated_at' => now(),
                ],
            );

            $logger->info('CalculateClinicEarningsJob: Earnings calculated successfully', [
                'clinic_id' => $this->clinicId,
                'total_revenue' => $totalRevenue,
                'platform_fee' => $totalPlatformFee,
                'clinic_earnings' => $totalClinicEarnings,
                'appointment_count' => $appointmentCount,
                'report_id' => $earningsReport->id,
                'correlation_id' => $this->resolvedCorrelationId,
            ]);
        } catch (\Throwable $e) {
            $logger->error('CalculateClinicEarningsJob: Failed to calculate earnings', [
                'clinic_id' => $this->clinicId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->resolvedCorrelationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }

    public function tags(): array
    {
        return [
            'clinic_earnings',
            "clinic:{$this->clinicId}",
            "correlation:{$this->resolvedCorrelationId}",
        ];
    }
}
