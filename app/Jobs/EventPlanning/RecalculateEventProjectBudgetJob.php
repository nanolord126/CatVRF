<?php declare(strict_types=1);

namespace App\Jobs\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RecalculateEventProjectBudgetJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $backoff = 60;

        /**
         * Create a new job instance.
         */
        public function __construct(
            private readonly int $projectId,
            private readonly string $correlationId
        ) {}

        /**
         * Execute the job.
         */
        public function handle(PricingService $pricingService): void
        {
            // 1. Audit Start (Canon 2026: Mandatory audit trace)
            Log::channel('audit')->info('[Job] Starting budget recalculation', [
                'correlation_id' => $this->correlationId,
                'project_id' => $this->projectId,
            ]);

            try {
                // 2. Transaction Scope (Canon 2026: Mutating records)
                DB::transaction(function () use ($pricingService) {
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
                    Log::channel('audit')->info('[Job] Budget recalculated successfully', [
                        'correlation_id' => $this->correlationId,
                        'project_id' => $this->projectId,
                        'old_budget' => $oldBudget,
                        'new_budget' => $totalSpent,
                        'status_changed' => $project->status !== 'draft',
                    ]);
                });

            } catch (Exception $e) {
                // 6. Error Audit Log (Canon 2026: Full stack trace)
                Log::channel('audit')->error('[Job] Budget recalculation failed', [
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
