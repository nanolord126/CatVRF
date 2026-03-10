<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class BusinessGroupGuard
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = tenant();
        $user = $request->user();

        if ($tenant && $user) {
            $groupId = $tenant->business_group_id ?? null;
            $userGroupId = $user->business_group_id ?? null;

            if ($groupId && $userGroupId && $groupId !== $userGroupId) {
                throw new TenantCouldNotBeIdentifiedById($tenant->id);
            }
        }

        return $next($request);
    }
}
