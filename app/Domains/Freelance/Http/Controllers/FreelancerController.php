<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelancerController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(): JsonResponse
        {
            try {
                $freelancers = Freelancer::where('is_active', true)
                    ->where('is_verified', true)
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $freelancers,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error listing freelancers', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to list freelancers',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $freelancer = Freelancer::with(['services', 'reviews'])->findOrFail($id);

                return response()->json([
                    'success' => true,
                    'data' => $freelancer,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error showing freelancer', [
                    'freelancer_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Freelancer not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function register(Request $request): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $validated = $request->all();
                return DB::transaction(function () use ($validated, $correlationId) {
                    $freelancer = Freelancer::create([
                        'tenant_id' => tenant()->id,
                        'user_id' => auth()->id(),
                        'full_name' => ($validated['full_name'] ?? null),
                        'bio' => ($validated['bio'] ?? null),
                        'hourly_rate' => ($validated['hourly_rate'] ?? null),
                        'skills' => ($validated['skills'] ?? []),
                        'languages' => ($validated['languages'] ?? []),
                        'experience_years' => ($validated['experience_years'] ?? 0),
                        'portfolio_url' => ($validated['portfolio_url'] ?? null),
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Freelancer registered', [
                        'freelancer_id' => $freelancer->id,
                        'user_id' => auth()->id(),
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $freelancer,
                        'correlation_id' => $correlationId,
                    ], 201);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error registering freelancer', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to register freelancer',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $freelancer = Freelancer::findOrFail($id);

                $this->authorize('update', $freelancer);

                $validated = $request->all();
                return DB::transaction(function () use ($validated, $freelancer, $correlationId) {
                    $freelancer->update($request->only([
                        'full_name', 'bio', 'hourly_rate', 'skills', 'languages',
                        'experience_years', 'portfolio_url', 'website',
                    ]));

                    Log::channel('audit')->info('Freelancer updated', [
                        'freelancer_id' => $freelancer->id,
                        'user_id' => auth()->id(),
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $freelancer,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error updating freelancer', [
                    'freelancer_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update freelancer',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $freelancer = Freelancer::findOrFail($id);

                $this->authorize('delete', $freelancer);

                return DB::transaction(function () use ($freelancer, $correlationId) {
                    $freelancer->delete();

                    Log::channel('audit')->info('Freelancer deleted', [
                        'freelancer_id' => $freelancer->id,
                        'user_id' => auth()->id(),
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error deleting freelancer', [
                    'freelancer_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete freelancer',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function topFreelancers(): JsonResponse
        {
            try {
                $topFreelancers = Freelancer::where('is_verified', true)
                    ->orderByDesc('rating')
                    ->limit(20)
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $topFreelancers,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error getting top freelancers', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get top freelancers',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function verify(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $freelancer = Freelancer::findOrFail($id);

                return DB::transaction(function () use ($freelancer, $correlationId) {
                    $freelancer->update(['is_verified' => true]);

                    Log::channel('audit')->info('Freelancer verified by admin', [
                        'freelancer_id' => $freelancer->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $freelancer,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error verifying freelancer', [
                    'freelancer_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify freelancer',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
