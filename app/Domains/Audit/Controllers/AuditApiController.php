<?php

declare(strict_types=1);

namespace App\Domains\Audit\Controllers;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Audit\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * AuditApiController — Internal API for audit log access.
 * Provides RESTful endpoints for programmatic audit log access.
 */
final class AuditApiController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * List audit logs with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_type' => 'sometimes|string|max:255',
            'subject_id' => 'sometimes|integer',
            'user_id' => 'sometimes|integer',
            'tenant_id' => 'sometimes|integer',
            'action' => 'sometimes|string|max:100',
            'from' => 'sometimes|date',
            'to' => 'sometimes|date|after:from',
            'limit' => 'sometimes|integer|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = AuditLog::query();

        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->has('action')) {
            $query->where('action', 'like', '%'.$request->action.'%');
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $limit = $request->input('limit', 50);

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Get single audit log by ID.
     */
    public function show(int $id): JsonResponse
    {
        $log = AuditLog::findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $log->id,
                'uuid' => $log->uuid,
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'user_id' => $log->user_id,
                'tenant_id' => $log->tenant_id,
                'business_group_id' => $log->business_group_id,
                'ip_address' => $log->ip_address,
                'device_fingerprint' => $log->device_fingerprint,
                'correlation_id' => $log->correlation_id,
                'old_values' => $log->getMaskedOldValues(),
                'new_values' => $log->getMaskedNewValues(),
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ],
        ]);
    }

    /**
     * Get audit logs by subject type and optional ID.
     */
    public function bySubject(Request $request, string $type, ?int $id = null): JsonResponse
    {
        $limit = $request->input('limit', 100);

        $logs = $this->auditService->getLogsForSubject($type, $id, $limit);

        return response()->json([
            'data' => $logs,
            'meta' => [
                'count' => $logs->count(),
            ],
        ]);
    }

    /**
     * Get audit logs by correlation ID.
     */
    public function byCorrelationId(string $correlationId): JsonResponse
    {
        $logs = $this->auditService->getLogsByCorrelationId($correlationId);

        return response()->json([
            'data' => $logs,
            'meta' => [
                'count' => $logs->count(),
            ],
        ]);
    }

    /**
     * Get audit logs by user ID.
     */
    public function byUser(Request $request, int $userId): JsonResponse
    {
        $limit = $request->input('limit', 100);

        $logs = $this->auditService->getLogsForUser($userId, $limit);

        return response()->json([
            'data' => $logs,
            'meta' => [
                'count' => $logs->count(),
            ],
        ]);
    }

    /**
     * Delete audit logs for user (GDPR compliance).
     */
    public function deleteByUser(int $userId): JsonResponse
    {
        $count = $this->auditService->deleteLogsForUser($userId);

        return response()->json([
            'message' => 'Audit logs deleted',
            'deleted_count' => $count,
        ]);
    }

    /**
     * Delete audit logs for subject.
     */
    public function deleteBySubject(string $type, ?int $id = null): JsonResponse
    {
        $count = $this->auditService->deleteLogsForSubject($type, $id);

        return response()->json([
            'message' => 'Audit logs deleted',
            'deleted_count' => $count,
        ]);
    }

    /**
     * Prune old audit logs based on retention policy.
     */
    public function prune(): JsonResponse
    {
        $count = $this->auditService->pruneOldLogs();

        return response()->json([
            'message' => 'Old audit logs pruned',
            'deleted_count' => $count,
        ]);
    }
}
