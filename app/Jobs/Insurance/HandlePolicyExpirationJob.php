<?php declare(strict_types=1);

namespace App\Jobs\Insurance;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class HandlePolicyExpirationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $backoff = 60;

        /**
         * Create a new job instance.
         */
        public function __construct(
            private ?string $correlationId = null,
    ) {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        /**
         * Execute the job logic.
         */
        public function handle(LogManager $logger, DatabaseManager $db): void
        {
            $logger->channel('audit')->info('[HandlePolicyExpirationJob] Job execution started', [
                'correlation_id' => $this->correlationId,
            ]);

            try {
                // Locate policies that are 'active' but 'expires_at' is in the past
                $expiredPoliciesQuery = InsurancePolicy::where('status', 'active')
                    ->where('expires_at', '<', now());

                $stats = [
                    'processed' => 0,
                    'errors' => 0,
                ];

                // Chunk processing for memory safety (Canon 2026 Batch logic)
                $expiredPoliciesQuery->chunk(100, function ($policies) use (&$stats, $logger, $db) {
                    foreach ($policies as $policy) {
                        try {
                            $db->transaction(function () use ($policy, $logger) {
                                $policy->update([
                                    'status' => 'expired',
                                    'correlation_id' => $this->correlationId,
                                ]);

                                // Potential for event dispatch: PolicyExpiredEvent::dispatch($policy);

                                $logger->channel('audit')->info('[HandlePolicyExpirationJob] Policy marked as EXPIRED', [
                                    'correlation_id' => $this->correlationId,
                                    'policy_uuid' => $policy->uuid,
                                    'expires_at' => $policy->expires_at->toDateTimeString(),
                                ]);
                            });

                            $stats['processed']++;

                        } catch (Exception $policyException) {
                            $logger->channel('audit')->error('[HandlePolicyExpirationJob] Failed to expire policy', [
                                'correlation_id' => $this->correlationId,
                                'policy_id' => $policy->id,
                                'error' => $policyException->getMessage(),
                            ]);
                            $stats['errors']++;
                        }
                    }
                });

                $logger->channel('audit')->info('[HandlePolicyExpirationJob] Finished processing policy expirations', [
                    'correlation_id' => $this->correlationId,
                    'stats' => $stats,
                ]);

            } catch (Exception $globalException) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($globalException->getMessage(), [
                    'exception' => $globalException::class,
                    'file' => $globalException->getFile(),
                    'line' => $globalException->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $logger->channel('audit')->critical('[HandlePolicyExpirationJob] GLOBAL CRITICAL FAILURE', [
                    'correlation_id' => $this->correlationId,
                    'error' => $globalException->getMessage(),
                    'trace' => $globalException->getTraceAsString(),
                ]);

                throw $globalException;
            }
        }

        /**
         * Tags for horizon/queue monitoring.
         */
        public function tags(): array
        {
            return ['insurance', 'expiration', 'cron', $this->correlationId];
        }
}
