<?php declare(strict_types=1);

namespace App\Domains\Freelance\Jobs;

use App\Domains\Freelance\Models\FreelanceContract;
use App\Domains\Freelance\Models\Freelancer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CalculateFreelancerEarningsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $freelancerId = 0,
        public readonly string $correlationId = '',
    ) {
        $this->onQueue('default');

    }

    public function handle(): void
    {
        $this->db->transaction(function () {
            $freelancer = Freelancer::find($this->freelancerId);
            if (!$freelancer) {
                $this->log->channel('audit')->warning('Freelancer not found for earnings calculation', [
                    'freelancer_id' => $this->freelancerId,
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            $totalEarned = FreelanceContract::where('freelancer_id', $this->freelancerId)
                ->where('status', 'completed')
                ->sum('amount_paid');

            $completedJobs = FreelanceContract::where('freelancer_id', $this->freelancerId)
                ->where('status', 'completed')
                ->count();

            $freelancer->update([
                'total_earned' => $totalEarned,
                'jobs_completed' => $completedJobs,
            ]);

            $this->log->channel('audit')->info('Freelancer earnings calculated', [
                'freelancer_id' => $this->freelancerId,
                'total_earned' => $totalEarned,
                'jobs_completed' => $completedJobs,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}

