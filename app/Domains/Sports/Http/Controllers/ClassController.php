<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Models\ClassSession;
use App\Domains\Sports\Models\Studio;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ClassController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function byStudio(int $studioId): JsonResponse
    {
        try {
            $classes = ClassSession::where('studio_id', $studioId)->where('is_active', true)->paginate(20);
            return response()->json(['success' => true, 'data' => $classes, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list classes'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $class = ClassSession::with(['studio', 'trainer', 'bookings'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $class, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Class not found'], 404);
        }
    }

    public function store(int $studioId): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $studio = Studio::findOrFail($studioId);
            $this->authorize('update', $studio);

            $validated = request()->validate([
                'trainer_id' => 'required|integer|exists:trainers,id',
                'name' => 'required|string|max:255',
                'starts_at' => 'required|date',
                'ends_at' => 'required|date|after:starts_at',
                'price' => 'required|numeric|min:0',
                'max_participants' => 'required|integer|min:1',
            ]);

            $class = ClassSession::create([
                'tenant_id' => tenant('id'),
                'studio_id' => $studioId,
                'trainer_id' => $validated['trainer_id'],
                'name' => $validated['name'],
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'price' => $validated['price'],
                'max_participants' => $validated['max_participants'],
                'is_active' => true,
            ]);

            Log::channel('audit')->info('Sports class created', [
                'correlation_id' => $correlationId,
                'class_id'       => $class->id,
                'studio_id'      => $studioId,
                'user_id'        => auth()->id(),
                'name'           => $class->name,
            ]);

            return response()->json(['success' => true, 'data' => $class], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create class'], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $class = ClassSession::findOrFail($id);
            $this->authorize('update', $class);

            $validated = request()->validate(['name' => 'sometimes|string', 'price' => 'sometimes|numeric']);
            $before = $class->getAttributes();
            $class->update($validated);

            Log::channel('audit')->info('Sports class updated', [
                'correlation_id' => $correlationId,
                'class_id'       => $class->id,
                'user_id'        => auth()->id(),
                'before'         => $before,
                'after'          => $validated,
            ]);

            return response()->json(['success' => true, 'data' => $class]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update class'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $class = ClassSession::findOrFail($id);
            $this->authorize('delete', $class);
            $this->fraudControlService->check(auth()->id() ?? 0, 'class_delete', 0, request()->ip(), null, $correlationId);
            $class->delete();

            Log::channel('audit')->info('Sports class deleted', [
                'correlation_id' => $correlationId,
                'class_id'       => $class->id,
                'user_id'        => auth()->id(),
            ]);

            return response()->json(['success' => true, 'message' => 'Class deleted']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete class'], 500);
        }
    }
}
