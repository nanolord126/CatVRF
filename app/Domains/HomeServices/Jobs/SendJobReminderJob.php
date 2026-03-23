<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Jobs;

use App\Domains\HomeServices\Models\ServiceJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimits;
use Illuminate\Queue\SerializesModels;

final class SendJobReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public int $jobId = 0,
        public ?string $correlationId = 'system',
    ) {}

    public function handle(): void
    {
        try {
            $job = ServiceJob::where('id', $this->jobId)->where('status', 'accepted')->firstOrFail();

            if (!$job->scheduled_at || $job->scheduled_at->lessThanOrEqualTo(now())) {
                return;
            }

            $hoursUntilJob = now()->diffInHours($job->scheduled_at);
            if ($hoursUntilJob <= 2) {
                $contractor = $job->contractor;
                $client = $job->client;

                \Log::channel('audit')->info('Job reminder sent', [
                    'job_id' => $this->jobId,
                    'contractor_id' => $contractor->id,
                    'client_id' => $client->id,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to send job reminder', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            $this->fail($e);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(4);
    }

    public function tags(): array
    {
        return ['home_services', 'reminders', 'job_' . $this->jobId];
    }

    public function onQueue(): string
    {
        return 'notifications';
    }
}
