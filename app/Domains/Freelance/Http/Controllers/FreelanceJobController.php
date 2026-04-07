<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FreelanceJobController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $jobs,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing jobs', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $job,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error showing job', [
                    'job_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Job not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $validated = $request->all();
                return $this->db->transaction(function () use ($validated, $correlationId) {
                    $job = FreelanceJob::create([
                        'tenant_id' => tenant()->id,
                        'client_id' => $request->user()?->id,
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
                        'posted_at' => Carbon::now(),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Freelance job posted', [
                        'job_id' => $job->id,
                        'client_id' => $request->user()?->id,
                        'budget_min' => ($validated['budget_min'] ?? null),
                        'budget_max' => ($validated['budget_max'] ?? null),
                        'correlation_id' => $correlationId,
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'success' => true,
                        'data' => $job,
                        'correlation_id' => $correlationId,
                    ], 201);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Error posting job', [
                    'client_id' => $request->user()?->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to post job',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $job = FreelanceJob::findOrFail($id);

                $this->authorize('update', $job);

                $validated = $request->all();
                return $this->db->transaction(function () use ($validated, $job, $correlationId) {
                    $job->update($request->only([
                        'title', 'description', 'categories', 'skills_required',
                        'budget_min', 'budget_max', 'duration_days',
                    ]));

                    $this->logger->info('Freelance job updated', [
                        'job_id' => $job->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'success' => true,
                        'data' => $job,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Error updating job', [
                    'job_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update job',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $job = FreelanceJob::findOrFail($id);

                $this->authorize('delete', $job);

                return $this->db->transaction(function () use ($job, $correlationId) {
                    $job->delete();

                    $this->logger->info('Freelance job deleted', [
                        'job_id' => $job->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'success' => true,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Error deleting job', [
                    'job_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return $this->db->transaction(function () use ($job, $correlationId) {
                    $job->update(['status' => 'closed']);

                    $this->logger->info('Freelance job closed', [
                        'job_id' => $job->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'success' => true,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Error closing job', [
                    'job_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to close job',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function myJobs(): JsonResponse
        {
            try {
                $jobs = FreelanceJob::where('client_id', $request->user()?->id)
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $jobs,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing my jobs', [
                    'client_id' => $request->user()?->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $totalBudget = FreelanceJob::sum($this->db->raw('(budget_min + budget_max) / 2'));

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'total_jobs' => $totalJobs,
                        'open_jobs' => $openJobs,
                        'completed_jobs' => $completedJobs,
                        'total_budget' => $totalBudget,
                    ],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error getting freelance stats', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to get stats',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
