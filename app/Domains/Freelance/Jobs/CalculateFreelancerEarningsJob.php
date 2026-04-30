<?php declare(strict_types=1);

namespace App\Domains\Freelance\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use DateTime;

final class CalculateFreelancerEarningsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $freelancerId,
        private readonly string $correlationId,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
        private readonly FraudControlService $fraud,
    ) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['freelance', 'job'];
    }

    public function handle(): void
        {
            $correlationId = $this->correlationId;
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId);
            $this->db->transaction(function () {
                $freelancer = \App\Domains\Freelance\Models\Freelancer::find($this->freelancerId);
                if (!$freelancer) {
                    $this->logger->warning('Freelancer not found for earnings calculation', [
                        'freelancer_id' => $this->freelancerId,
                        'correlation_id' => $this->correlationId,
                    ]);
                    return;
                }

                $totalEarned = \App\Domains\Freelance\Models\FreelanceContract::where('freelancer_id', $this->freelancerId)
                    ->where('status', 'completed')
                    ->sum('amount_paid');

                $completedJobs = \App\Domains\Freelance\Models\FreelanceContract::where('freelancer_id', $this->freelancerId)
                    ->where('status', 'completed')
                    ->count();

                $freelancer->update([
                    'total_earned' => $totalEarned,
                    'jobs_completed' => $completedJobs,
                ]);

                $this->logger->info('Freelancer earnings calculated', [
                    'freelancer_id' => $this->freelancerId,
                    'total_earned' => $totalEarned,
                    'jobs_completed' => $completedJobs,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        }

        public function retryUntil(): \DateTime
        {
            return (new \Carbon\Carbon())->addHours(24);
        }


    public function failed(\Throwable $exception): void
    {
        $this->logger->error('freelance job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}

