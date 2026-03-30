<?php declare(strict_types=1);

namespace App\Services\Consulting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsultingB2BService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param string $correlationId Unified audit trace.
         */
        public function __construct(
            private string $correlationId = '',
        ) {
            $this->correlationId = $correlationId ?: (string) Str::uuid();
        }

        /**
         * Enroll a business group into a subscription retainer.
         */
        public function enrollBusinessRetainer(int $businessGroupId, int $consultingFirmId, int $serviceId): ConsultingContract
        {
            FraudControlService::check();

            return DB::transaction(function() use ($businessGroupId, $consultingFirmId, $serviceId) {
                $firm = ConsultingFirm::findOrFail($consultingFirmId);
                $service = \App\Models\Consulting\ConsultingService::findOrFail($serviceId);

                if (!$service->isSubscription()) {
                    throw new \Exception("Subscription service required for retainer enrollment.");
                }

                Log::channel('audit')->info('Enrolling Business into Retainer', [
                    'business_group_id' => $businessGroupId,
                    'firm_id' => $consultingFirmId,
                    'correlation_id' => $this->correlationId,
                ]);

                $contract = ConsultingContract::create([
                   'tenant_id' => $firm->tenant_id,
                   'consulting_firm_id' => $consultingFirmId,
                   'client_id' => $businessGroupId, // In B2B mode, client_id might refer to a BusinessGroup or its rep
                   'contract_number' => "RET-" . strtoupper(Str::random(8)),
                   'status' => 'draft',
                   'total_amount' => $service->price,
                   'started_at' => now(),
                   'ended_at' => now()->addYear(),
                   'terms' => [
                       'retainer_type' => 'monthly',
                       'billing_cycle' => '1st day',
                       'hours_included' => 20
                   ],
                   'correlation_id' => $this->correlationId,
                ]);

                return $contract;
            });
        }

        /**
         * Track and fulfill B2B project deliverables.
         */
        public function fulfillProjectDeliverable(int $projectId, string $deliverableName): void
        {
            FraudControlService::check();

            DB::transaction(function() use ($projectId, $deliverableName) {
                $project = ConsultingProject::findOrFail($projectId);

                Log::channel('audit')->info('Fulfilling B2B Project Deliverable', [
                    'project_id' => $projectId,
                    'deliverable' => $deliverableName,
                    'correlation_id' => $this->correlationId,
                ]);

                $deliverables = $project->deliverables ?? [];
                foreach ($deliverables as &$d) {
                    if ($d['item'] === $deliverableName) {
                        $d['status'] = 'completed';
                        $d['fulfilled_at'] = now()->toIso8601String();
                        break;
                    }
                }

                $project->update(['deliverables' => $deliverables]);

                if ($this->allDeliverablesCompleted($deliverables)) {
                    $project->update(['status' => 'completed']);
                }
            });
        }

        /**
         * Helper to check if all deliverables are done.
         */
        private function allDeliverablesCompleted(array $deliverables): bool
        {
            if (count($deliverables) === 0) return false;

            foreach ($deliverables as $d) {
                if ($d['status'] !== 'completed') return false;
            }
            return true;
        }

        /**
         * Get unfulfilled B2B deliverables for a client.
         */
        public function getPendingDeliverables(int $clientId): Collection
        {
            return ConsultingProject::where('client_id', $clientId)
                 ->active()
                 ->get()
                 ->flatMap(function($project) {
                     return array_filter($project->deliverables ?? [], fn($d) => $d['status'] === 'pending');
                 });
        }

        /**
         * Calculate monthly billing for a business under retainer.
         */
        public function calculateMonthlyRetainerBilling(int $contractId): int
        {
            $contract = ConsultingContract::findOrFail($contractId);
            $baseAmount = $contract->total_amount;

            // Logical check for over-usage if hours are tracked
            $totalMinutes = ConsultingSession::where('client_id', $contract->client_id)
                ->whereMonth('scheduled_at', now()->month)
                ->sum('duration_minutes');

            $includedMinutes = ($contract->terms['hours_included'] ?? 0) * 60;

            if ($totalMinutes > $includedMinutes) {
                 // Arbitrary over-usage logic 500 RUB per extra minute
                 $extraMinutes = $totalMinutes - $includedMinutes;
                 $baseAmount += $extraMinutes * 50000;
            }

            return $baseAmount;
        }
}
