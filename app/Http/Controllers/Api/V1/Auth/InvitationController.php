<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Traits\Guards\HighRiskActionGuard;
use Carbon\CarbonImmutable;

use App\Http\Controllers\Controller;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\Role;
use App\Models\Tenant;

final class InvitationController extends Controller
{use HighRiskActionGuard;
    
    /**
     * Send tenant invitation
     * POST /api/v1/tenants/{tenant}/invitations
     */
    public function store(Request $request, string $tenantId): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:owner,manager,employee,accountant'],
            'message' => ['nullable', 'string'],
        // Check if critical changes are blocked for manager+ role invitations
        if (in_array($validated['role'], ['owner', 'manager'], true)) {
            if ($this->isCriticalChangesBlocked()) {
                return $this->getCriticalChangesBlockedResponse();
            }
        }

        ]);

        $tenant = Tenant::findOrFail($tenantId);

        $user = auth()->user();

        if (! $user || ! $tenant->userHasRole($user->id, [Role::Owner, Role::Manager])) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by_user_id' => $user->id,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'role' => $validated['role'],
            'message' => $validated['message'],
        ]);

        // Send invitation email
        // $invitation->notify(new TenantInvitationNotification($invitation));

        return new JsonResponse([
            'message' => 'Invitation sent successfully.',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'expires_at' => $invitation->expires_at,
            ],
        ], 201);
    }

    /**
     * Accept invitation
     * POST /api/v1/tenants/invitations/{token}/accept
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = TenantInvitation::byToken($token)->pending()->firstOrFail();

        if ($invitation->isExpired()) {
            return new JsonResponse(['error' => 'Invitation has expired'], 400);
        }

        $user = auth()->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        if ($user->email !== $invitation->email) {
            return new JsonResponse(['error' => 'Invitation email does not match your email'], 400);
        }

        // Attach user to tenant
        $user->tenants()->attach($invitation->tenant_id, [
            'role' => $invitation->role,
            'is_active' => true,
            'accepted_at' => CarbonImmutable::now(),
        ]);

        // Update user role if needed
        if ($user->role === Role::Customer) {
            $user->update(['role' => $invitation->role]);
        }

        $invitation->accept();

        return new JsonResponse([
            'message' => 'Invitation accepted successfully.',
        ]);
    }

    /**
     * List pending invitations for tenant
     * GET /api/v1/tenants/{tenant}/invitations
     */
    public function index(Request $request, string $tenantId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        $user = auth()->user();

        if (! $user || ! $tenant->userHasRole($user->id, [Role::Owner, Role::Manager])) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $invitations = $tenant->invitations()->with('invitedBy')->pending()->latest()->get();

        return new JsonResponse([
            'invitations' => $invitations->map(fn ($inv) => [
                'id' => $inv->id,
                'email' => $inv->email,
                'name' => $inv->name,
                'role' => $inv->role->value,
                'invited_by' => $inv->invitedBy->name,
                'expires_at' => $inv->expires_at,
            ]),
        ]);
    }

    /**
     * Cancel invitation
     * DELETE /api/v1/tenants/invitations/{invitation}
     */
    public function destroy(Request $request, int $invitationId): JsonResponse
    {
        $invitation = TenantInvitation::findOrFail($invitationId);

        $tenant = $invitation->tenant;
        $user = auth()->user();

        if (! $user || ! $tenant->userHasRole($user->id, [Role::Owner, Role::Manager])) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $invitation->delete();

        return new JsonResponse([
            'message' => 'Invitation cancelled successfully.',
        ]);
    }
}
