<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FitnessClassController extends Controller
{

    public function __construct(
            private readonly ClassService $classService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $classes = FitnessClass::where('is_active', true)
                    ->with(['gym', 'trainer'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $classes, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to list classes', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $class = FitnessClass::with(['gym', 'trainer', 'schedules'])->findOrFail($id);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $class, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }

        public function myClasses(): JsonResponse
        {
            try {
                $trainer = $request->user()->trainer;
                $classes = FitnessClass::where('trainer_id', $trainer->id)->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $classes, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function getSchedule(int $id): JsonResponse
        {
            try {
                $schedules = ClassSchedule::where('fitness_class_id', $id)
                    ->where('is_cancelled', false)
                    ->orderBy('scheduled_at')
                    ->get();

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $schedules, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');
            try {
                $request->validate([
                    'gym_id' => 'required|exists:gyms,id',
                    'trainer_id' => 'required|exists:trainers,id',
                    'name' => 'required|string',
                    'class_type' => 'required|string',
                    'duration_minutes' => 'required|integer',
                    'max_participants' => 'required|integer',
                    'price_per_class' => 'required|numeric',
                ]);

                $class = $this->classService->createClass(
                    $request->input('gym_id'),
                    $request->input('trainer_id'),
                    $request->input('name'),
                    $request->input('description', ''),
                    $request->input('class_type'),
                    $request->input('duration_minutes'),
                    $request->input('max_participants'),
                    $request->input('price_per_class'),
                    $correlationId
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $class, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                $this->logger->error('Failed to create class', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');
            try {
                $class = FitnessClass::findOrFail($id);
                $this->authorize('update', $class);

                $this->classService->updateClass($class, $request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $class->fresh(), 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function delete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $class = FitnessClass::findOrFail($id);
                $this->authorize('delete', $class);

                $class->delete();

                $this->logger->info('Class deleted', ['class_id' => $id, 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function addSchedule(int $classId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $request->validate([
                    'scheduled_at' => 'required|datetime',
                    'max_participants' => 'required|integer',
                ]);

                $schedule = ClassSchedule::create([
                    'tenant_id' => tenant()?->id,
                    'fitness_class_id' => $classId,
                    'day_of_week' => now()->parse($request->input('scheduled_at'))->format('l'),
                    'start_time' => now()->parse($request->input('scheduled_at'))->format('H:i:s'),
                    'end_time' => now()->parse($request->input('scheduled_at'))->addMinutes(FitnessClass::find($classId)->duration_minutes)->format('H:i:s'),
                    'scheduled_at' => $request->input('scheduled_at'),
                    'max_participants' => $request->input('max_participants'),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $schedule, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function updateSchedule(int $scheduleId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $schedule = ClassSchedule::findOrFail($scheduleId);
                $schedule->update(array_merge($request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), ['correlation_id' => $correlationId]));

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $schedule, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function cancelSchedule(int $scheduleId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $schedule = ClassSchedule::findOrFail($scheduleId);
                $schedule->update(['is_cancelled' => true, 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }
}
