<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\B2BLead;
use Modules\CatCRM\Domain\Entities\B2BContact;
use Modules\CatCRM\Domain\Entities\B2BDeal;
use Modules\CatCRM\Domain\Enums\LeadStatus;
use Illuminate\Support\Facades\DB;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

final class B2BLeadService
{
    use WithAuditLogging;

    public function __construct(
        private readonly string $correlationId,
        private readonly AuditService $auditService,
    ) {}

    public function createLead(array $data): B2BLead
    {
        return DB::transaction(function () use ($data) {
            $lead = B2BLead::create([
                'tenant_id' => $data['tenant_id'],
                'vertical_id' => $data['vertical_id'],
                'company_name' => $data['company_name'],
                'contact_person' => $data['contact_person'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'],
                'requirement' => $data['requirement'],
                'budget_range' => $data['budget_range'],
                'category' => $data['category'],
                'status' => LeadStatus::New,
                'source' => $data['source'],
                'correlation_id' => $this->correlationId,
            ]);

            if (!empty($data['contacts'])) {
                foreach ($data['contacts'] as $contactData) {
                    B2BContact::create([
                        'tenant_id' => $lead->tenant_id,
                        'lead_id' => $lead->id,
                        'name' => $contactData['name'],
                        'email' => $contactData['email'],
                        'phone' => $contactData['phone'],
                        'position' => $contactData['position'] ?? null,
                        'is_primary' => $contactData['is_primary'] ?? false,
                    ]);
                }
            }

            return $lead;
        });
    }

    public function assignToStaff(B2BLead $lead, int $staffId, ?int $teamId = null): B2BLead
    {
        $lead->update(['assigned_to_id' => $staffId]);
        if ($teamId) {
            $lead->update(['assigned_team_id' => $teamId]);
        }
        return $lead->fresh();
    }

    public function linkWarehouse(B2BLead $lead, int $warehouseId): B2BLead
    {
        return $lead->linkWarehouse($warehouseId) ? $lead->fresh() : $lead;
    }

    public function convertToDeal(B2BLead $lead): B2BDeal
    {
        return $lead->convertToDeal();
    }
}
