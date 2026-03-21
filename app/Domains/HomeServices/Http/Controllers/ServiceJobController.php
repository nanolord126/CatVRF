<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;

use App\Domains\HomeServices\Models\ServiceJob;
use App\Domains\HomeServices\Models\ServiceDispute;
use App\Domains\HomeServices\Services\JobService;
use App\Domains\HomeServices\Jobs\SendJobReminderJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ServiceJobController
{
    public function __construct(private JobService $jobService) {}

    public function create(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $validated = request()->validate([
                'service_listing_id' => 'required|integer|exists:service_listings,id',
                'address' => 'required|string',
                'description' => 'required|string',
                'scheduled_at' => 'nullable|date',
            ]);

            $correlationId = Str::uuid();

            $job = \DB::transaction(fn() => $this->jobService->createJob(
                $validated['service_listing_id'],
                auth()->id(),
                $validated['address'],
                $validated['description'],
                $correlationId
            ));

            SendJobReminderJob::dispatch($job->id, $correlationId);

            return response()->json(['success' => true, 'data' => $job, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create job'], 500);
        }
    }

    public function myJobs(): JsonResponse
    {
        try {
            $jobs = ServiceJob::where('client_id', auth()->id())
                ->orWhere('contractor_id', \App\Domains\HomeServices\Models\Contractor::where('user_id', auth()->id())->value('id'))
                ->with(['serviceListing', 'contractor', 'client'])
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $jobs, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch jobs'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $job = ServiceJob::with(['serviceListing', 'contractor', 'client', 'reviews', 'disputes'])->findOrFail($id);
            $this->authorize('view', $job);

            return response()->json(['success' => true, 'data' => $job, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Job not found'], 404);
        }
    }

    public function accept(int $id): JsonResponse
    {
        try {
            $job = ServiceJob::findOrFail($id);
            $this->authorize('accept', $job);

            $correlationId = Str::uuid();
            $job->update(['status' => 'accepted', 'correlation_id' => $correlationId]);

            return response()->json(['success' => true, 'data' => $job, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to accept job'], 500);
        }
    }

    public function start(int $id): JsonResponse
    {
        try {
            $job = ServiceJob::findOrFail($id);
            $correlationId = Str::uuid();

            $job->update(['status' => 'in_progress', 'started_at' => now(), 'correlation_id' => $correlationId]);

            return response()->json(['success' => true, 'data' => $job, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to start job'], 500);
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $job = ServiceJob::findOrFail($id);
            $correlationId = Str::uuid();

            \DB::transaction(fn() => $this->jobService->completeJob($job, $correlationId));

            return response()->json(['success' => true, 'data' => $job, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to complete job'], 500);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $job = ServiceJob::findOrFail($id);
            $this->authorize('cancel', $job);

            $correlationId = Str::uuid();
            \DB::transaction(fn() => $this->jobService->cancelJob($job, request()->input('reason', 'User cancelled'), $correlationId));

            return response()->json(['success' => true, 'message' => 'Job cancelled', 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to cancel job'], 500);
        }
    }

    public function createDispute(int $jobId): JsonResponse
    {
        try {
            $job = ServiceJob::findOrFail($jobId);
            $this->authorize('create', ServiceDispute::class);

            $validated = request()->validate([
                'type' => 'required|in:quality_issue,incomplete_work,safety_concern,other',
                'description' => 'required|string',
                'evidence' => 'nullable|array',
            ]);

            $correlationId = Str::uuid();

            $dispute = ServiceDispute::create([
                'tenant_id' => tenant('id'),
                'job_id' => $jobId,
                'initiator_id' => auth()->id(),
                'type' => $validated['type'],
                'description' => $validated['description'],
                'evidence' => $validated['evidence'] ?? [],
                'correlation_id' => $correlationId,
            ]);

            return response()->json(['success' => true, 'data' => $dispute, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create dispute'], 500);
        }
    }

    public function myDisputes(): JsonResponse
    {
        try {
            $disputes = ServiceDispute::where('initiator_id', auth()->id())
                ->with(['job'])
                ->paginate(10);

            return response()->json(['success' => true, 'data' => $disputes, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch disputes'], 500);
        }
    }

    public function resolveDispute(int $id): JsonResponse
    {
        try {
            $dispute = ServiceDispute::findOrFail($id);
            $this->authorize('resolve', $dispute);

            $validated = request()->validate([
                'resolution' => 'required|string',
                'refund_amount' => 'nullable|numeric|min:0',
            ]);

            $correlationId = Str::uuid();

            $dispute->update([
                'status' => 'resolved',
                'resolution' => $validated['resolution'],
                'refund_amount' => $validated['refund_amount'] ?? null,
                'resolved_by' => auth()->user()->email,
                'resolved_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json(['success' => true, 'data' => $dispute, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to resolve dispute'], 500);
        }
    }

    public function resolve(int $id): JsonResponse
    {
        try {
            $job = ServiceJob::findOrFail($id);
            $this->authorize('view', $job);

            $validated = request()->validate(['status' => 'required|in:completed,cancelled']);

            $job->update(['status' => $validated['status']]);

            return response()->json(['success' => true, 'data' => $job, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to resolve job'], 500);
        }
    }
}
