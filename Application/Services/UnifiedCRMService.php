<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\Customer;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\B2BLead;
use Modules\CatCRM\Domain\Entities\B2BDeal;
use Modules\CatCRM\Domain\Entities\Task;
use Modules\CatCRM\Domain\Enums\CustomerType;
use Modules\CatCRM\Domain\Enums\DealStatus;
use Modules\CatCRM\Domain\Enums\LeadStatus;
use Illuminate\Database\DatabaseManager;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

final class UnifiedCRMService
{
    use WithAuditLogging;

    public function __construct(
        private readonly string $correlationId,
        private readonly AuditService $auditService,
        private readonly CustomerService $customerService,
        private readonly DealService $dealService,
        private readonly B2BLeadService $b2bLeadService,
        private readonly CRMStaffIntegrationService $staffIntegration,
        private readonly CRMWarehouseIntegrationService $warehouseIntegration,
        private readonly CRMInventoryIntegrationService $inventoryIntegration,
        private readonly DatabaseManager $db,
    ) {}

    public function createB2CCustomer(array $data): Customer
    {
        $data['type'] = CustomerType::Individual;
        return $this->customerService->createCustomer($data);
    }

    public function createB2BCustomer(array $data): Customer
    {
        $data['type'] = CustomerType::Business;
        return $this->customerService->createCustomer($data);
    }

    public function createB2BLead(array $data): B2BLead
    {
        $lead = $this->b2bLeadService->createLead($data);

        if (!empty($data['assign_to_staff'])) {
            $this->staffIntegration->assignLeadToStaff(
                $lead,
                \App\Models\User::find($data['assign_to_staff'])
            );
        }

        if (!empty($data['warehouse_id'])) {
            $this->warehouseIntegration->linkLeadToWarehouse($lead, $data['warehouse_id']);
        }

        if (!empty($data['inventory_items'])) {
            $this->inventoryIntegration->createQuoteRequest($lead, $data['inventory_items']);
        }

        return $lead;
    }

    public function convertLeadToDeal(B2BLead $lead): B2BDeal
    {
        return $this->db->transaction(function () use ($lead) {
            $deal = $this->b2bLeadService->convertToDeal($lead);

            if ($lead->default_warehouse_id) {
                $deal->warehouse_id = $lead->default_warehouse_id;
                $deal->save();
            }

            Task::create([
                'tenant_id' => $deal->tenant_id,
                'assigned_to_id' => $deal->assigned_to_id,
                'title' => "Подготовить контракт: {$deal->company_name}",
                'type' => 'document',
                'priority' => 4,
                'due_date' => now()->addDays(3),
                'entity_type' => 'b2b_deal',
                'entity_id' => $deal->id,
            ]);

            return $deal;
        });
    }

    public function processDealLifecycle(B2BDeal $deal, string $action, array $params = []): B2BDeal
    {
        return $this->db->transaction(function () use ($deal, $action, $params) {
            switch ($action) {
                case 'reserve_inventory':
                    if (!empty($params['items'])) {
                        $this->warehouseIntegration->reserveInventoryForDeal($deal, $params['items']);
                    }
                    break;

                case 'confirm_contract':
                    $deal->update([
                        'contract_start_date' => $params['start_date'] ?? now(),
                        'contract_end_date' => $params['end_date'] ?? now()->addYear(),
                    ]);
                    break;

                case 'win_deal':
                    $wonStage = \Modules\CatCRM\Domain\Entities\Stage::where('is_won_stage', true)->first();
                    if ($wonStage) {
                        $deal->moveToStage($wonStage);
                    }
                    
                    if ($deal->lead_id) {
                        $lead = B2BLead::find($deal->lead_id);
                        if ($lead) {
                            $lead->update(['status' => LeadStatus::Won]);
                        }
                    }
                    break;

                case 'lose_deal':
                    $lostStage = \Modules\CatCRM\Domain\Entities\Stage::where('is_lost_stage', true)->first();
                    if ($lostStage) {
                        $deal->moveToStage($lostStage);
                        $deal->lost_reason = $params['reason'] ?? 'Unknown';
                    }
                    
                    if ($deal->lead_id) {
                        $lead = B2BLead::find($deal->lead_id);
                        if ($lead) {
                            $lead->update(['status' => LeadStatus::Lost]);
                        }
                    }
                    
                    $this->warehouseIntegration->releaseReservedInventory($deal);
                    break;
            }

            return $deal->fresh();
        });
    }

    public function getCustomer360(int $customerId): array
    {
        $customer = Customer::with(['deals', 'interactions', 'tasks', 'tags'])->findOrFail($customerId);

        return [
            'customer' => $customer,
            'ltv' => $this->customerService->getCustomerLTV($customer),
            'deals' => $customer->deals->map(fn($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'value' => $d->value,
                'status' => $d->status->label(),
                'stage' => $d->stage?->name,
            ]),
            'interactions' => $customer->interactions->take(10),
            'pending_tasks' => $customer->tasks()->where('status', 'pending')->get(),
            'activity_timeline' => $this->buildActivityTimeline($customer),
        ];
    }

    public function getLead360(int $leadId): array
    {
        $lead = B2BLead::with(['contacts', 'interactions', 'tasks', 'tags', 'warehouse'])->findOrFail($leadId);

        return [
            'lead' => $lead,
            'contacts' => $lead->contacts,
            'estimated_value' => $lead->estimateValue(),
            'inventory_quote' => $lead->inventoryRequests()->latest()->first(),
            'pending_tasks' => $lead->tasks()->where('status', 'pending')->get(),
            'activity_timeline' => $this->buildLeadActivityTimeline($lead),
        ];
    }

    private function buildActivityTimeline(Customer $customer): array
    {
        $timeline = [];

        foreach ($customer->interactions as $interaction) {
            $timeline[] = [
                'type' => 'interaction',
                'date' => $interaction->created_at,
                'description' => $interaction->type . ': ' . $interaction->description,
            ];
        }

        foreach ($customer->deals as $deal) {
            $timeline[] = [
                'type' => 'deal',
                'date' => $deal->created_at,
                'description' => "Сделка создана: {$deal->title}",
            ];
        }

        return collect($timeline)->sortByDesc('date')->values()->toArray();
    }

    private function buildLeadActivityTimeline(B2BLead $lead): array
    {
        $timeline = [];

        foreach ($lead->interactions as $interaction) {
            $timeline[] = [
                'type' => 'interaction',
                'date' => $interaction->created_at,
                'description' => $interaction->type . ': ' . $interaction->description,
            ];
        }

        $timeline[] = [
            'type' => 'lead_created',
            'date' => $lead->created_at,
            'description' => "Лид создан: {$lead->company_name}",
        ];

        return collect($timeline)->sortByDesc('date')->values()->toArray();
    }
}
