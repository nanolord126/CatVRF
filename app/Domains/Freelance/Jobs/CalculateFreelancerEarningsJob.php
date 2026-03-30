<?php declare(strict_types=1);

namespace App\Domains\Freelance\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CalculateFreelancerEarningsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public readonly int $freelancerId = 0,
            public readonly string $correlationId = '',
        ) {
            $this->onQueue('default');

        }

        public function handle(): void
        {
            DB::transaction(function () {
                $freelancer = Freelancer::find($this->freelancerId);
                if (!$freelancer) {
                    Log::channel('audit')->warning('Freelancer not found for earnings calculation', [
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

                Log::channel('audit')->info('Freelancer earnings calculated', [
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
