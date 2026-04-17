<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Psr\Log\LoggerInterface;

final class VeganSubscriptionBatchRenewalJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        /**
         * The number of times the job may be attempted.
         */
        public int $tries = 3;

        /**
         * The number of seconds to wait before retrying the job.
         */
        public int $backoff = 60;

        /**
         * Create a new job instance.
         */
        public function __construct(
            private string $correlationId = '',
            private array $metaData = [], private readonly LoggerInterface $logger) {}

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
            $correlationId = $this->correlationId ?: (string) Str::uuid();

            $this->logger->info('LAYER-8: Vegan Subscription Batch RENEWAL START', [
                'correlation_id' => $correlationId,
                'job_id' => $this->job->getJobId() ?? 'N/A',
            ]);

            try {
                $renewedCount = $service->renewBatch($correlationId);

                $this->logger->info('LAYER-8: Vegan Subscription Batch RENEWAL SUCCESS', [
                    'count' => $renewedCount,
                    'correlation_id' => $correlationId,
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('LAYER-8: Vegan Subscription Batch RENEWAL FAILED', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->fail($e);
            }
        }
    }

