<?php declare(strict_types=1);

namespace App\Domains\Freelance\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class CalculateFreelancerEarningsJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(public int $freelancerId = 0,
            private string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {
            $this->onQueue('default');

        }

        public function handle(): void
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () {
                $freelancer = Freelancer::find($this->freelancerId);
                if (!$freelancer) {
                    $this->logger->warning('Freelancer not found for earnings calculation', [
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
            return Carbon::now()->addHours(24);
        }
}

