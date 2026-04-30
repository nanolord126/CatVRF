<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\Customer;
use Modules\CatCRM\Domain\Entities\B2BLead;
use Modules\CatCRM\Domain\Entities\Task;
use App\Models\User;
use App\Models\Team;
use Illuminate\Database\DatabaseManager;

final class CRMStaffIntegrationService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}
    public function assignCustomerToManager(Customer $customer, User $manager): bool
    {
        return $this->db->transaction(function () use ($customer, $manager) {
            $customer->update(['assigned_to_id' => $manager->id]);

            Task::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'assigned_to_id' => $manager->id,
                'title' => "Связаться с клиентом {$customer->getDisplayName()}",
                'type' => 'call',
                'priority' => 3,
                'due_date' => now()->addHours(24),
                'entity_type' => 'customer',
                'entity_id' => $customer->id,
            ]);

            return true;
        });
    }

    public function assignLeadToStaff(B2BLead $lead, User $staff, ?Team $team = null): bool
    {
        return $this->db->transaction(function () use ($lead, $staff, $team) {
            $lead->assignToUser($staff);
            
            if ($team) {
                $lead->assignToTeam($team);
            }

            Task::create([
                'tenant_id' => $lead->tenant_id,
                'assigned_to_id' => $staff->id,
                'title' => "Обработать лид: {$lead->company_name}",
                'type' => 'call',
                'priority' => $lead->priority,
                'due_date' => now()->addHours(4),
                'entity_type' => 'b2b_lead',
                'entity_id' => $lead->id,
            ]);

            return true;
        });
    }

    public function getAvailableStaffForVertical(int $tenantId, string $vertical): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereHas('roles', function ($q) use ($vertical) {
                $q->where('name', "crm_{$vertical}_manager");
            })
            ->with(['tasks' => function ($q) {
                $q->where('status', 'pending')->count();
            }])
            ->get()
            ->sortByDesc('tasks_count');
    }

    public function autoAssignLead(B2BLead $lead): ?User
    {
        $availableStaff = $this->getAvailableStaffForVertical($lead->tenant_id, $lead->vertical_id);
        
        if ($availableStaff->isEmpty()) {
            return null;
        }

        $manager = $availableStaff->first();
        $this->assignLeadToStaff($lead, $manager);
        
        return $manager;
    }
}
