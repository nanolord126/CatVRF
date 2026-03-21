<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Models\ClassSession;
use App\Domains\Sports\Models\Studio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ClassController
{
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
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

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

            return response()->json(['success' => true, 'data' => $class], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create class'], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $class = ClassSession::findOrFail($id);
            $this->authorize('update', $class);

            $validated = request()->validate(['name' => 'sometimes|string', 'price' => 'sometimes|numeric']);
            $class->update($validated);

            return response()->json(['success' => true, 'data' => $class]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update class'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $class = ClassSession::findOrFail($id);
            $this->authorize('delete', $class);
            $class->delete();

            return response()->json(['success' => true, 'message' => 'Class deleted']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete class'], 500);
        }
    }
}
