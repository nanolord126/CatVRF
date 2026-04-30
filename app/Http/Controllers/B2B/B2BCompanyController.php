<?php

declare(strict_types=1);

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\BusinessGroup;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Str;

/**
 * B2BCompanyController — Управление профилем компании, филиалами и командой.
 *
 * Все методы требуют X-B2B-API-Key (через B2BApiMiddleware).
 * business_group доступен через $request->attributes->get('b2b_business_group').
 */
final class B2BCompanyController extends Controller
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ResponseFactory $response,
        private readonly AuditService $audit,
    ) {}

    /** Получить информацию о компании. */
    public function show(Request $request): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $company = $this->db->table('business_groups')
            ->where('id', $group->id)
            ->first();

        // Получаем информацию о кредитной линии
        $creditInfo = $this->db->table('b2b_credit_lines')
            ->where('business_group_id', $group->id)
            ->first();

        return $this->response->json([
            'success' => true,
            'data' => [
                'company' => $company,
                'credit' => $creditInfo,
            ],
        ]);
    }

    /** Обновить информацию о компании. */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_person' => ['sometimes', 'string', 'max:255'],
            'contact_phone' => ['sometimes', 'string', 'max:50'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'actual_address' => ['sometimes', 'string', 'max:500'],
            'website' => ['sometimes', 'url', 'max:255'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $this->db->table('business_groups')
            ->where('id', $group->id)
            ->update(array_merge($validated, ['updated_at' => now()]));

        $this->logUpdated('business_group', [
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }

    /** Получить список филиалов. */
    public function branches(Request $request): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $branches = $this->db->table('b2b_branches')
            ->where('business_group_id', $group->id)
            ->orderBy('name')
            ->get();

        return $this->response->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    /** Создать филиал. */
    public function createBranch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'manager' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $branchId = $this->db->table('b2b_branches')->insertGetId([
            'business_group_id' => $group->id,
            'name' => $validated['name'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'manager' => $validated['manager'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logCreated('b2b_branch', [
            'branch_id' => $branchId,
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'data' => ['id' => $branchId],
            'correlation_id' => $correlationId,
        ], 201);
    }

    /** Обновить филиал. */
    public function updateBranch(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:500'],
            'city' => ['sometimes', 'string', 'max:100'],
            'manager' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $updated = $this->db->table('b2b_branches')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->update(array_merge($validated, ['updated_at' => now()]));

        if (!$updated) {
            return $this->response->json(['success' => false, 'message' => 'Branch not found'], 404);
        }

        $this->logUpdated('b2b_branch', [
            'branch_id' => $id,
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }

    /** Удалить филиал. */
    public function deleteBranch(Request $request, int $id): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $deleted = $this->db->table('b2b_branches')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->delete();

        if (!$deleted) {
            return $this->response->json(['success' => false, 'message' => 'Branch not found'], 404);
        }

        $this->logDeleted('b2b_branch', [
            'branch_id' => $id,
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }

    /** Получить список команды. */
    public function team(Request $request): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $team = $this->db->table('b2b_team_members')
            ->where('business_group_id', $group->id)
            ->orderBy('name')
            ->get();

        return $this->response->json([
            'success' => true,
            'data' => $team,
        ]);
    }

    /** Добавить члена команды. */
    public function addTeamMember(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['sometimes', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'role' => ['required', 'in:admin,manager,accountant,viewer'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $memberId = $this->db->table('b2b_team_members')->insertGetId([
            'business_group_id' => $group->id,
            'name' => $validated['name'],
            'position' => $validated['position'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logCreated('b2b_team_member', [
            'member_id' => $memberId,
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'data' => ['id' => $memberId],
            'correlation_id' => $correlationId,
        ], 201);
    }

    /** Обновить члена команды. */
    public function updateTeamMember(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'position' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'role' => ['sometimes', 'in:admin,manager,accountant,viewer'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $updated = $this->db->table('b2b_team_members')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->update(array_merge($validated, ['updated_at' => now()]));

        if (!$updated) {
            return $this->response->json(['success' => false, 'message' => 'Team member not found'], 404);
        }

        $this->logUpdated('b2b_team_member', [
            'member_id' => $id,
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }

    /** Удалить члена команды. */
    public function deleteTeamMember(Request $request, int $id): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $deleted = $this->db->table('b2b_team_members')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->delete();

        if (!$deleted) {
            return $this->response->json(['success' => false, 'message' => 'Team member not found'], 404);
        }

        $this->logDeleted('b2b_team_member', [
            'member_id' => $id,
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }
}
