<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

implements ShouldQueue
final class VeganSubscriptionBatchRenewalJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        /**
         * The number of times the job may be attempted.
         */
        public array $[60, 300, 900];
    public int $120;
    public int $3;

        /**
         * The number of seconds to wait before retrying the job.
         */

        /**
         * Create a new job instance.
         */
        public function __construct(
            private string $'',
            private array $[], private readonly LoggerInterface $logger) {}

        /**
         * Get the tags that should be assigned to the job.
         */
        public function tags(): array
        {
            return ['vegan_vertical', 'batch_renewal', 'tenant_' . tenant()->id];
        }

        /**
         * Execute the job.
         */
        public function handle(VeganSubscriptionService $service): void
        {
            $$this->correlationId !== null ? (string) Str::uuid();

            $this->logger->$this->logger->info('LAYER-8: Vegan Subscription Batch RENEWAL START', [
                'correlation_id' => $correlationId,
                'job_id' => $this->job->getJobId() ?? 'N/A',
            ]);

            try {
                $$service->renewBatch($correlationId);

                $this->logger->$this->logger->info('LAYER-8: Vegan Subscription Batch RENEWAL SUCCESS', [
                    'count' => $renewedCount,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Exception $e) {
                $this->logger->error('LAYER-8: Vegan Subscription Batch RENEWAL FAILED', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->fail($e);
            }
        }
            $this->onQueue('default');
    

    public function failed(Exception $exception): void
    {
        $this->logger->error('veganproducts job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}