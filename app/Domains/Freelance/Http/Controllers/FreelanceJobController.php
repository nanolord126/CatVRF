<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceJobController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(Request $request): JsonResponse
        {
            try {
                $query = FreelanceJob::where('status', 'open');

                if ($request->has('category')) {
                    $query->whereJsonContains('categories', $request->input('category'));
                }

                if ($request->has('budget_min')) {
                    $query->where('budget_min', '>=', $request->input('budget_min'));
                }

                $jobs = $query->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $jobs,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error listing jobs', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to list jobs',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $job = FreelanceJob::with('proposals')->findOrFail($id);

                return response()->json([
                    'success' => true,
                    'data' => $job,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error showing job', [
                    'job_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Job not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $validated = $request->all();
                return DB::transaction(function () use ($validated, $correlationId) {
                    $job = FreelanceJob::create([
                        'tenant_id' => tenant()->id,
                        'client_id' => auth()->id(),
                        'title' => ($validated['title'] ?? null),
                        'description' => ($validated['description'] ?? null),
                        'categories' => ($validated['categories'] ?? []),
                        'skills_required' => ($validated['skills_required'] ?? []),
                        'job_type' => ($validated['job_type'] ?? 'one-time'),
                        'pricing_type' => ($validated['pricing_type'] ?? 'fixed'),
                        'budget_min' => ($validated['budget_min'] ?? null),
                        'budget_max' => ($validated['budget_max'] ?? null),
                        'duration_days' => ($validated['duration_days'] ?? null),
                        'status' => 'open',
                        'posted_at' => now(),
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Freelance job posted', [
                        'job_id' => $job->id,
                        'client_id' => auth()->id(),
                        'budget_min' => ($validated['budget_min'] ?? null),
                        'budget_max' => ($validated['budget_max'] ?? null),
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $job,
                        'correlation_id' => $correlationId,
                    ], 201);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error posting job', [
                    'client_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to post job',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $job = FreelanceJob::findOrFail($id);

                $this->authorize('update', $job);

                $validated = $request->all();
                return DB::transaction(function () use ($validated, $job, $correlationId) {
                    $job->update($request->only([
                        'title', 'description', 'categories', 'skills_required',
                        'budget_min', 'budget_max', 'duration_days',
                    ]));

                    Log::channel('audit')->info('Freelance job updated', [
                        'job_id' => $job->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $job,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error updating job', [
                    'job_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update job',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $job = FreelanceJob::findOrFail($id);

                $this->authorize('delete', $job);

                return DB::transaction(function () use ($job, $correlationId) {
                    $job->delete();

                    Log::channel('audit')->info('Freelance job deleted', [
                        'job_id' => $job->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error deleting job', [
                    'job_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete job',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function close(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $job = FreelanceJob::findOrFail($id);

                $this->authorize('close', $job);

                return DB::transaction(function () use ($job, $correlationId) {
                    $job->update(['status' => 'closed']);

                    Log::channel('audit')->info('Freelance job closed', [
                        'job_id' => $job->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error closing job', [
                    'job_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to close job',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function myJobs(): JsonResponse
        {
            try {
                $jobs = FreelanceJob::where('client_id', auth()->id())
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $jobs,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error listing my jobs', [
                    'client_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to list jobs',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function stats(): JsonResponse
        {
            try {
                $totalJobs = FreelanceJob::count();
                $openJobs = FreelanceJob::where('status', 'open')->count();
                $completedJobs = FreelanceJob::where('status', 'closed')->count();
                $totalBudget = FreelanceJob::sum(DB::raw('(budget_min + budget_max) / 2'));

                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_jobs' => $totalJobs,
                        'open_jobs' => $openJobs,
                        'completed_jobs' => $completedJobs,
                        'total_budget' => $totalBudget,
                    ],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error getting freelance stats', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get stats',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
