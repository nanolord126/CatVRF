<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GymController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(): JsonResponse
        {
            try {
                $gyms = Gym::where('is_verified', true)
                    ->where('is_active', true)
                    ->with(['trainers', 'fitnessClasses'])
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $gyms,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to list gyms', ['error' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $gym = Gym::with(['trainers', 'fitnessClasses', 'memberships'])
                    ->findOrFail($id);

                return response()->json([
                    'success' => true,
                    'data' => $gym,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);
            try {
                request()->validate([
                    'name' => 'required|string',
                    'address' => 'required|string',
                    'monthly_membership_price' => 'required|numeric',
                ]);

                $gym = Gym::create([
                    'tenant_id' => tenant('id'),
                    'name' => request('name'),
                    'address' => request('address'),
                    'description' => request('description'),
                    'monthly_membership_price' => request('monthly_membership_price'),
                    'annual_membership_price' => request('annual_membership_price', 0),
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Gym created', ['gym_id' => $gym->id, 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'data' => $gym, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to create gym', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);
            try {
                $gym = Gym::findOrFail($id);
                $this->authorize('update', $gym);

                $gym->update(array_merge(request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), ['correlation_id' => $correlationId]));

                Log::channel('audit')->info('Gym updated', ['gym_id' => $gym->id, 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'data' => $gym, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to update gym', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function delete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $gym = Gym::findOrFail($id);
                $this->authorize('delete', $gym);

                $gym->delete();

                Log::channel('audit')->info('Gym deleted', ['gym_id' => $id, 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to delete gym', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }
}
