<?php declare(strict_types=1);

namespace App\Domains\Fitness\Http\Controllers;

use App\Domains\Fitness\Models\FitnessClass;
use App\Domains\Fitness\Models\ClassSchedule;
use App\Domains\Fitness\Services\ClassService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FitnessClassController
{
    public function __construct(
        private readonly ClassService $classService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $classes = FitnessClass::where('is_active', true)
                ->with(['gym', 'trainer'])
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $classes, 'correlation_id' => Str::uuid()]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to list classes', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $class = FitnessClass::with(['gym', 'trainer', 'schedules'])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $class, 'correlation_id' => Str::uuid()]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function myClasses(): JsonResponse
    {
        try {
            $trainer = auth()->user()->trainer;
            $classes = FitnessClass::where('trainer_id', $trainer->id)->paginate(20);

            return response()->json(['success' => true, 'data' => $classes, 'correlation_id' => Str::uuid()]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function getSchedule(int $id): JsonResponse
    {
        try {
            $schedules = ClassSchedule::where('fitness_class_id', $id)
                ->where('is_cancelled', false)
                ->orderBy('scheduled_at')
                ->get();

            return response()->json(['success' => true, 'data' => $schedules, 'correlation_id' => Str::uuid()]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);
        try {
            request()->validate([
                'gym_id' => 'required|exists:gyms,id',
                'trainer_id' => 'required|exists:trainers,id',
                'name' => 'required|string',
                'class_type' => 'required|string',
                'duration_minutes' => 'required|integer',
                'max_participants' => 'required|integer',
                'price_per_class' => 'required|numeric',
            ]);

            $class = $this->classService->createClass(
                request('gym_id'),
                request('trainer_id'),
                request('name'),
                request('description', ''),
                request('class_type'),
                request('duration_minutes'),
                request('max_participants'),
                request('price_per_class'),
                $correlationId
            );

            return response()->json(['success' => true, 'data' => $class, 'correlation_id' => $correlationId], 201);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create class', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);
        try {
            $class = FitnessClass::findOrFail($id);
            $this->authorize('update', $class);

            $this->classService->updateClass($class, request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), $correlationId);

            return response()->json(['success' => true, 'data' => $class->fresh(), 'correlation_id' => $correlationId]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function delete(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $class = FitnessClass::findOrFail($id);
            $this->authorize('delete', $class);

            $class->delete();

            Log::channel('audit')->info('Class deleted', ['class_id' => $id, 'correlation_id' => $correlationId]);

            return response()->json(['success' => true, 'correlation_id' => $correlationId]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function addSchedule(int $classId): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            request()->validate([
                'scheduled_at' => 'required|datetime',
                'max_participants' => 'required|integer',
            ]);

            $schedule = ClassSchedule::create([
                'tenant_id' => tenant('id'),
                'fitness_class_id' => $classId,
                'day_of_week' => now()->parse(request('scheduled_at'))->format('l'),
                'start_time' => now()->parse(request('scheduled_at'))->format('H:i:s'),
                'end_time' => now()->parse(request('scheduled_at'))->addMinutes(FitnessClass::find($classId)->duration_minutes)->format('H:i:s'),
                'scheduled_at' => request('scheduled_at'),
                'max_participants' => request('max_participants'),
                'correlation_id' => $correlationId,
            ]);

            return response()->json(['success' => true, 'data' => $schedule, 'correlation_id' => $correlationId], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function updateSchedule(int $scheduleId): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $schedule = ClassSchedule::findOrFail($scheduleId);
            $schedule->update(array_merge(request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), ['correlation_id' => $correlationId]));

            return response()->json(['success' => true, 'data' => $schedule, 'correlation_id' => $correlationId]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function cancelSchedule(int $scheduleId): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $schedule = ClassSchedule::findOrFail($scheduleId);
            $schedule->update(['is_cancelled' => true, 'correlation_id' => $correlationId]);

            return response()->json(['success' => true, 'correlation_id' => $correlationId]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
