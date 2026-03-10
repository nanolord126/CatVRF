<?php
namespace App\Policies;
use App\Models\User;
use App\Domains\Advertising\Models\AdCampaign;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdCampaignPolicy extends BaseSecurityPolicy {
    use HandlesAuthorization;

    public function viewAny(User $user): bool {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'advertiser']);
    }

    public function view(User $user, AdCampaign $campaign): bool {
        if ($user->tenant_id !== $campaign->tenant_id) return false;
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager'])) return true;
        return $campaign->user_id === $user->id;
    }

    public function create(User $user): bool {
        return $user->tenant_id !== null && $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'advertiser']);
    }

    public function update(User $user, AdCampaign $campaign): bool {
        if ($user->tenant_id !== $campaign->tenant_id) return false;
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager'])) return true;
        return $campaign->user_id === $user->id && in_array($campaign->status, ['draft', 'scheduled']);
    }

    public function pause(User $user, AdCampaign $campaign): bool {
        if ($user->tenant_id !== $campaign->tenant_id) return false;
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']) || 
               ($campaign->user_id === $user->id && $campaign->status === 'active');
    }

    public function delete(User $user, AdCampaign $campaign): bool {
        if ($user->tenant_id !== $campaign->tenant_id) return false;
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }
}
