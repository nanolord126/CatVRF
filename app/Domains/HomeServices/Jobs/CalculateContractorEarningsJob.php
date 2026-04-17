<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Jobs;


use Carbon\Carbon;

use Psr\Log\LoggerInterface;
use App\Domains\HomeServices\Models\Contractor;
use App\Domains\HomeServices\Models\ContractorEarning;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
final class CalculateContractorEarningsJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public function __construct(
        private readonly FraudControlService $fraud,
        private ?string $correlationId = 'system', private readonly LoggerInterface $logger) {

    }

    public function handle(): void
    {
        try {
            $now = Carbon::now();
            $month = $now->month;
            $year = $now->year;

            Contractor::where('is_active', true)->chunk(50, function ($contractors) use ($month, $year) {
                foreach ($contractors as $contractor) {
                    $this->calculateEarnings($contractor, $month, $year);
                }
            });

            $this->logger->info('Contractor earnings calculated', [
                'month' => $month,
                'year' => $year,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to calculate earnings', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            $this->fail($e);
        }
    }

    private function calculateEarnings(Contractor $contractor, int $month, int $year): void
    {
        $jobs = $contractor->jobs()
            ->where('status', 'completed')
            ->whereMonth('completed_at', $month)
            ->whereYear('completed_at', $year)
            ->get();

        $totalRevenue = $jobs->sum('base_amount');
        $totalCommission = $jobs->sum('commission_amount');
        $contractorEarnings = $totalRevenue - $totalCommission;
        $avgRating = $contractor->reviews()->avg('rating') ?? 0;

        ContractorEarning::updateOrCreate(
            ['tenant_id' => $contractor->tenant_id, 'contractor_id' => $contractor->id, 'period_month' => $month, 'period_year' => $year],
            [
                'total_revenue' => $totalRevenue,
                'total_commission' => $totalCommission,
                'contractor_earnings' => $contractorEarnings,
                'total_jobs' => $jobs->count(),
                'completed_jobs' => $jobs->count(),
                'average_rating' => $avgRating,
            ]
        );
    }

    public function retryUntil(): \DateTime
    {
        return Carbon::now()->addHours(6);
    }

    public function tags(): array
    {
        return ['home_services', 'earnings', 'daily'];
    }

    public function onQueue(): string
    {
        return 'default';
    }
}

