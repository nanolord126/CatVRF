<?php
namespace App\Services;

use App\Models\BusinessGroup;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

class BranchManager {
    public function createBranch(array $data, int $ownerId, string $groupId) {
        $tenantId = Str::slug($data['name']);
        
        $tenant = Tenant::create([
            'id' => $tenantId,
            'business_group_id' => $groupId,
            'parent_id' => $data['parent_id'] ?? null,
            'inn' => $data['inn'] ?? null,
            'name' => $data['name'],
            'correlation_id' => Str::uuid(),
        ]);

        $user = User::create([
            'name' => "Manager " . $data['name'],
            'email' => $data['email'],
            'password' => bcrypt(Str::random(12)),
            'business_group_id' => $groupId,
            'tenant_id' => $tenantId,
        ]);

        return $tenant;
    }
}
