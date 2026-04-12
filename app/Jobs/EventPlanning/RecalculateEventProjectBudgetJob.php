<?php declare(strict_types=1);

namespace App\Jobs\EventPlanning;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class RecalculateEventProjectBudgetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $backoff = 60;

        /**
         * Create a new job instance.
         */
        public function __construct(
            private readonly int $projectId,
            private readonly string $correlationId,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
    ) {}

        /**
         * Execute the job.
         */
        public function handle(PricingService $pricingService): void
        {
            // 1. Audit Start (Canon 2026: Mandatory audit trace)
            $this->logger->channel('audit')->info('[Job] Starting budget recalculation', [
                'correlation_id' => $this->correlationId,
                'project_id' => $this->projectId,
            ]);

            try {
                // 2. Transaction Scope (Canon 2026: Mutating records)
                $this->db->transaction(function () use ($pricingService) {
                    // Lock for update to prevent race conditions during recalculation
                    $project = EventProject::where('id', $this->projectId)->lockForUpdate()->firstOrFail();

                    // 3. Calculation logic (Simulation for high-density code)
                    // In a real scenario, we aggregate all bookings, venues, and packages
                    $totalSpent = $project->bookings()
                        ->where('status', 'confirmed')
                        ->sum('total_price');

                    // 4. Update the project state
                    $oldBudget = $project->budget_spent;
                    $project->budget_spent = (int)$totalSpent;

                    // Mark completion if appropriate (Simple logic for vertical growth)
                    if ($project->status === 'draft' && $totalSpent > 0) {
                        $project->status = 'active';
                    }

                    $project->save();

                    // 5. Success Audit Log
                    $this->logger->channel('audit')->info('[Job] Budget recalculated successfully', [
                        'correlation_id' => $this->correlationId,
                        'project_id' => $this->projectId,
                        'old_budget' => $oldBudget,
                        'new_budget' => $totalSpent,
                        'status_changed' => $project->status !== 'draft',
                    ]);
                });

            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                // 6. Error Audit Log (Canon 2026: Full stack trace)
                $this->logger->channel('audit')->error('[Job] Budget recalculation failed', [
                    'correlation_id' => $this->correlationId,
                    'project_id' => $this->projectId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Release back to queue if retries remain
                throw $e;
            }
        }

        /**
         * Get tags for the job (Filament/Horizon monitoring).
         */
        public function tags(): array
        {
            return [
                'event-planning',
                'project:' . $this->projectId,
                'correlation:' . $this->correlationId,
            ];
        }
}
