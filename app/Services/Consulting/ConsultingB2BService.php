<?php

declare(strict_types=1);

namespace App\Services\Consulting;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use App\Models\Consulting\ConsultingContract;
use App\Models\Consulting\ConsultingFirm;
use App\Models\Consulting\ConsultingProject;
use App\Models\Consulting\ConsultingSession;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use App\Models\Consulting\ConsultingService;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final readonly class ConsultingB2BService
{
    use WithAuditLogging;

    /**
     * @param  string  $correlationId  Unified audit trace.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly FraudControlService $fraud,
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly AuditService $audit,
    ) {}

    /**
     * Enroll a business group into a subscription retainer.
     */
    public function enrollBusinessRetainer(int $businessGroupId, int $consultingFirmId, int $serviceId): ConsultingContract
    {
        $this->fraud->check($this->guard->id(), 'b2b_enroll_retainer', $this->request->ip());

        return $this->db->transaction(function () use ($businessGroupId, $consultingFirmId, $serviceId) {
            $firm = ConsultingFirm::findOrFail($consultingFirmId);
            $service = ConsultingService::findOrFail($serviceId);

            if (! $service->isSubscription()) {
                throw new \DomainException('Subscription service required for retainer enrollment.');
            }

            $this->logger->channel('audit')->$this->logger->info('Enrolling Business into Retainer', [
                'business_group_id' => $businessGroupId,
                'firm_id' => $consultingFirmId,
                'correlation_id' => $this->correlationId(),
            ]);

            $contract = ConsultingContract::create([
                'tenant_id' => $firm->tenant_id,
                'consulting_firm_id' => $consultingFirmId,
                'client_id' => $businessGroupId, // In B2B mode, client_id might refer to a BusinessGroup or its rep
                'contract_number' => 'RET-'.strtoupper(Str::random(8)),
                'status' => 'draft',
                'total_amount' => $service->price,
                'started_at' => CarbonImmutable::now(),
                'ended_at' => CarbonImmutable::now()->addYear(),
                'terms' => [
                    'retainer_type' => 'monthly',
                    'billing_cycle' => '1st day',
                    'hours_included' => 20,
                ],
                'correlation_id' => $this->correlationId(),
            ]);

            return $contract;
        });
    }

    /**
     * Track and fulfill B2B project deliverables.
     */
    public function fulfillProjectDeliverable(int $projectId, string $deliverableName): void
    {
        $this->fraud->check($this->guard->id(), 'b2b_fulfill_deliverable', $this->request->ip());

        $this->db->transaction(function () use ($projectId, $deliverableName) {
            $project = ConsultingProject::findOrFail($projectId);

            $this->logger->channel('audit')->$this->logger->info('Fulfilling B2B Project Deliverable', [
                'project_id' => $projectId,
                'deliverable' => $deliverableName,
                'correlation_id' => $this->correlationId(),
            ]);

            $deliverables = $project->deliverables ?? [];
            foreach ($deliverables as &$d) {
                if ($d['item'] === $deliverableName) {
                    $d['status'] = 'completed';
                    $d['fulfilled_at'] = CarbonImmutable::now()->toIso8601String();
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
     * Get unfulfilled B2B deliverables for a client.
     */
    public function getPendingDeliverables(int $clientId): Collection
    {
        return ConsultingProject::where('client_id', $clientId)
            ->active()
            ->get()
            ->flatMap(function ($project) {
                return array_filter($project->deliverables ?? [], fn ($d) => $d['status'] === 'pending');
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
            ->whereMonth('scheduled_at', CarbonImmutable::now()->month)
            ->sum('duration_minutes');

        $includedMinutes = ($contract->terms['hours_included'] ?? 0) * 60;

        if ($totalMinutes > $includedMinutes) {
            // Arbitrary over-usage logic 500 RUB per extra minute
            $extraMinutes = $totalMinutes - $includedMinutes;
            $baseAmount += $extraMinutes * 50000;
        }

        return $baseAmount;
    }

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

    /**
     * Helper to check if all deliverables are done.
     */
    private function allDeliverablesCompleted(array $deliverables): bool
    {
        if (count($deliverables) === 0) {
            return false;
        }

        foreach ($deliverables as $d) {
            if ($d['status'] !== 'completed') {
                return false;
            }
        }

        return true;
    }
}
