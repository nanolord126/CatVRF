<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class GymController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $gyms = Gym::where('is_verified', true)
                    ->where('is_active', true)
                    ->with(['trainers', 'fitnessClasses'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $gyms,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to list gyms', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $gym = Gym::with(['trainers', 'fitnessClasses', 'memberships'])
                    ->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $gym,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');
            try {
                $request->validate([
                    'name' => 'required|string',
                    'address' => 'required|string',
                    'monthly_membership_price' => 'required|numeric',
                ]);

                $gym = Gym::create([
                    'tenant_id' => tenant()?->id,
                    'name' => $request->input('name'),
                    'address' => $request->input('address'),
                    'description' => $request->input('description'),
                    'monthly_membership_price' => $request->input('monthly_membership_price'),
                    'annual_membership_price' => $request->input('annual_membership_price', 0),
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Gym created', ['gym_id' => $gym->id, 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $gym, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                $this->logger->error('Failed to create gym', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');
            try {
                $gym = Gym::findOrFail($id);
                $this->authorize('update', $gym);

                $gym->update(array_merge($request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), ['correlation_id' => $correlationId]));

                $this->logger->info('Gym updated', ['gym_id' => $gym->id, 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $gym, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to update gym', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function delete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $gym = Gym::findOrFail($id);
                $this->authorize('delete', $gym);

                $gym->delete();

                $this->logger->info('Gym deleted', ['gym_id' => $id, 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to delete gym', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }
}
