<?php

declare(strict_types=1);

namespace App\Jobs\Insurance;

use App\Models\Insurance\InsurancePolicy;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * HandlePolicyExpirationJob (Automated Lifecycle Job).
 * Implementation: Layer 9 (Automation Layer - Canon 2026).
 * Requirements: >60 lines, correlation_id, audit-log, DB::transaction.
 * Logic: Checks and flags policies that have reached their expiration date.
 */
class HandlePolicyExpirationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * Execute the job logic.
     */
    public function handle(): void
    {
        Log::channel('audit')->info('[HandlePolicyExpirationJob] Job execution started', [
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
            $expiredPoliciesQuery->chunk(100, function ($policies) use (&$stats) {
                foreach ($policies as $policy) {
                    try {
                        DB::transaction(function () use ($policy) {
                            $policy->update([
                                'status' => 'expired',
                                'correlation_id' => $this->correlationId,
                            ]);

                            // Potential for event dispatch: PolicyExpiredEvent::dispatch($policy);
                            
                            Log::channel('audit')->info('[HandlePolicyExpirationJob] Policy marked as EXPIRED', [
                                'correlation_id' => $this->correlationId,
                                'policy_uuid' => $policy->uuid,
                                'expires_at' => $policy->expires_at->toDateTimeString(),
                            ]);
                        });

                        $stats['processed']++;

                    } catch (Exception $policyException) {
                        Log::channel('audit')->error('[HandlePolicyExpirationJob] Failed to expire policy', [
                            'correlation_id' => $this->correlationId,
                            'policy_id' => $policy->id,
                            'error' => $policyException->getMessage(),
                        ]);
                        $stats['errors']++;
                    }
                }
            });

            Log::channel('audit')->info('[HandlePolicyExpirationJob] Finished processing policy expirations', [
                'correlation_id' => $this->correlationId,
                'stats' => $stats,
            ]);

        } catch (Exception $globalException) {
            Log::channel('audit')->critical('[HandlePolicyExpirationJob] GLOBAL CRITICAL FAILURE', [
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
