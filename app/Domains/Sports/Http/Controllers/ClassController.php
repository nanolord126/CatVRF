<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ClassController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function byStudio(int $studioId): JsonResponse
        {
            try {
                $classes = ClassSession::where('studio_id', $studioId)->where('is_active', true)->paginate(20);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $classes, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to list classes'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $class = ClassSession::with(['studio', 'trainer', 'bookings'])->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $class, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Class not found'], 404);
            }
        }

        public function store(int $studioId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $studio = Studio::findOrFail($studioId);
                $this->authorize('update', $studio);

                $validated = $request->validate([
                    'trainer_id' => 'required|integer|exists:trainers,id',
                    'name' => 'required|string|max:255',
                    'starts_at' => 'required|date',
                    'ends_at' => 'required|date|after:starts_at',
                    'price' => 'required|numeric|min:0',
                    'max_participants' => 'required|integer|min:1',
                ]);

                $class = ClassSession::create([
                    'tenant_id' => tenant()?->id,
                    'studio_id' => $studioId,
                    'trainer_id' => $validated['trainer_id'],
                    'name' => $validated['name'],
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'],
                    'price' => $validated['price'],
                    'max_participants' => $validated['max_participants'],
                    'is_active' => true,
                ]);

                $this->logger->info('Sports class created', [
                    'correlation_id' => $correlationId,
                    'class_id'       => $class->id,
                    'studio_id'      => $studioId,
                    'user_id'        => $request->user()?->id,
                    'name'           => $class->name,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $class], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to create class'], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $class = ClassSession::findOrFail($id);
                $this->authorize('update', $class);

                $validated = $request->validate(['name' => 'sometimes|string', 'price' => 'sometimes|numeric']);
                $before = $class->getAttributes();
                $class->update($validated);

                $this->logger->info('Sports class updated', [
                    'correlation_id' => $correlationId,
                    'class_id'       => $class->id,
                    'user_id'        => $request->user()?->id,
                    'before'         => $before,
                    'after'          => $validated,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $class]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to update class'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $class = ClassSession::findOrFail($id);
                $this->authorize('delete', $class);
                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'class_delete', amount: 0, correlationId: $correlationId ?? '');
                $class->delete();

                $this->logger->info('Sports class deleted', [
                    'correlation_id' => $correlationId,
                    'class_id'       => $class->id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Class deleted']);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to delete class'], 500);
            }
        }
}
