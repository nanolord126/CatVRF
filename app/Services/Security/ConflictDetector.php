<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\User;

final readonly class ConflictDetector
{
    public function __construct() {}

    /**
     * Check for conflicts
     */
    public function hasConflict(int $requesterId, int $approverId): array
    {
        $conflicts = [];

        if ($this->isSelfApproval($requesterId, $approverId)) {
            $conflicts[] = 'self_approval';
        }

        if ($this->isSameTeam($requesterId, $approverId)) {
            $conflicts[] = 'same_team';
        }

        if ($this->isReportingLineConflict($requesterId, $approverId)) {
            $conflicts[] = 'reporting_line';
        }

        if ($this->isRelatedBusiness($requesterId, $approverId)) {
            $conflicts[] = 'related_business';
        }

        return [
            'has_conflict' => count($conflicts) > 0,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Check for self-approval
     */
    public function isSelfApproval(int $requesterId, int $approverId): bool
    {
        return $requesterId === $approverId;
    }

    /**
     * Check for same team
     */
    public function isSameTeam(int $requesterId, int $approverId): bool
    {
        $users = User::whereIn('id', [$requesterId, $approverId])
            ->select(['id', 'team_id'])
            ->get()
            ->keyBy('id');

        $requesterTeam = $users->get($requesterId)?->team_id;
        $approverTeam = $users->get($approverId)?->team_id;

        return $requesterTeam !== null && $requesterTeam === $approverTeam;
    }

    /**
     * Check for reporting line conflict
     */
    public function isReportingLineConflict(int $requesterId, int $approverId): bool
    {
        $users = User::whereIn('id', [$requesterId, $approverId])
            ->select(['id', 'manager_id'])
            ->get()
            ->keyBy('id');

        // Check if approver reports to requester
        $approverManagerId = $users->get($approverId)?->manager_id;

        if ($approverManagerId === $requesterId) {
            return true;
        }

        // Check if requester reports to approver (reverse conflict)
        $requesterManagerId = $users->get($requesterId)?->manager_id;

        if ($requesterManagerId === $approverId) {
            return true;
        }

        return false;
    }

    /**
     * Check for related business conflict
     */
    public function isRelatedBusiness(int $requesterId, int $approverId): bool
    {
        $users = User::whereIn('id', [$requesterId, $approverId])
            ->select(['id', 'business_group_id'])
            ->get()
            ->keyBy('id');

        $requesterBusinessGroupId = $users->get($requesterId)?->business_group_id;
        $approverBusinessGroupId = $users->get($approverId)?->business_group_id;

        if (! $requesterBusinessGroupId || ! $approverBusinessGroupId) {
            return false;
        }

        return $requesterBusinessGroupId === $approverBusinessGroupId;
    }

    /**
     * Get eligible approvers (excluding conflicts)
     */
    public function getEligibleApprovers(int $requesterId, array $requiredRoles): array
    {
        $requester = User::find($requesterId, ['id', 'team_id', 'business_group_id']);

        $query = User::where('id', '!=', $requesterId)
            ->where('status', 'active');

        if (! empty($requiredRoles)) {
            $query->whereHas('roles', function ($q) use ($requiredRoles) {
                $q->whereIn('name', $requiredRoles);
            });
        }

        // Exclude same team
        if ($requester?->team_id) {
            $query->where('team_id', '!=', $requester->team_id);
        }

        // Exclude reporting line
        $query->where('manager_id', '!=', $requesterId);

        // Exclude related business
        if ($requester?->business_group_id) {
            $query->where('business_group_id', '!=', $requester->business_group_id);
        }

        return $query->get()->toArray();
    }
}
