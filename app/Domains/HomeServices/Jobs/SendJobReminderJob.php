<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Jobs;


use Carbon\Carbon;



use App\Services\FraudControlService;
use Psr\Log\LoggerInterface;
use App\Domains\HomeServices\Models\ServiceJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
final class SendJobReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly FraudControlService $fraud,
        private int $jobId = 0,
        private ?string $correlationId = 'system', private readonly LoggerInterface $logger) {

    }

    public function handle(): void
    {
        try {
            $job = ServiceJob::where('id', $this->jobId)->where('status', 'accepted')->firstOrFail();

            if (!$job->scheduled_at || $job->scheduled_at->lessThanOrEqualTo(Carbon::now())) {
                return;
            }

            $hoursUntilJob = Carbon::now()->diffInHours($job->scheduled_at);
            if ($hoursUntilJob <= 2) {
                $contractor = $job->contractor;
                $client = $job->client;

                $this->logger->info('Job reminder sent', [
                    'job_id' => $this->jobId,
                    'contractor_id' => $contractor->id,
                    'client_id' => $client->id,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send job reminder', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            $this->fail($e);
        }
    }

    public function retryUntil(): \DateTime
    {
        return Carbon::now()->addHours(4);
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
