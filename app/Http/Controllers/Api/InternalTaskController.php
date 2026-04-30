<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InternalTaskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final readonly class InternalTaskController extends Controller
{
    public function __construct(
        private readonly InternalTaskService $taskService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $tasks = \App\Models\InternalTask::where('tenant_id', $request->tenant_id)
            ->with(['assignee', 'controller', 'checkpoints'])
            ->orderBy('due_date', 'asc')
            ->get();

        return response()->json([
            'data' => $tasks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
            'creator_id' => 'required|integer',
            'assignee_id' => 'nullable|integer',
            'controller_id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'in:low,medium,high,urgent',
            'type' => 'in:notification,campaign,report,review,other',
            'due_date' => 'nullable|date',
            'checkpoints' => 'nullable|array',
            'checkpoints.*.title' => 'required|string',
        ]);

        $task = $this->taskService->createTask($validated, $request->header('X-Correlation-ID', ''));

        return response()->json([
            'data' => $task->load(['assignee', 'controller', 'checkpoints']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $task = \App\Models\InternalTask::with(['assignee', 'controller', 'checkpoints'])->findOrFail($id);

        return response()->json([
            'data' => $task,
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,review,completed,cancelled',
            'user_id' => 'nullable|integer',
        ]);

        $task = $this->taskService->updateStatus(
            $id,
            $validated['status'],
            $validated['user_id'] ?? null,
            $request->header('X-Correlation-ID', '')
        );

        return response()->json([
            'data' => $task,
        ]);
    }

    public function addCheckpoint(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $checkpoint = $this->taskService->addCheckpoint($id, $validated, $request->header('X-Correlation-ID', ''));

        return response()->json([
            'data' => $checkpoint,
        ], 201);
    }

    public function completeCheckpoint(Request $request, int $checkpointId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
        ]);

        $checkpoint = $this->taskService->completeCheckpoint(
            $checkpointId,
            $validated['user_id'],
            $request->header('X-Correlation-ID', '')
        );

        return response()->json([
            'data' => $checkpoint,
        ]);
    }

    public function getOverdue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'controller_id' => 'required|integer',
            'tenant_id' => 'required|integer',
        ]);

        $tasks = $this->taskService->getOverdueTasksForController($validated['controller_id'], $validated['tenant_id']);

        return response()->json([
            'data' => $tasks,
        ]);
    }

    public function getStatistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $stats = $this->taskService->getTaskStatistics($validated['tenant_id']);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
