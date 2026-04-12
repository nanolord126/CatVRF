<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ServiceJobController extends Controller
{


    public function __construct(private JobService $jobService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function create(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $validated = $request->validate([
                    'service_listing_id' => 'required|integer|exists:service_listings,id',
                    'address' => 'required|string',
                    'description' => 'required|string',
                    'scheduled_at' => 'nullable|date',
                ]);

                $job = $this->db->transaction(fn() => $this->jobService->createJob(
                    $validated['service_listing_id'],
                    $request->user()?->id,
                    $validated['address'],
                    $validated['description'],
                    $correlationId
                ));

                $this->logger->info('HomeService job created', [
                    'correlation_id' => $correlationId,
                    'job_id'         => $job->id ?? null,
                    'user_id'        => $request->user()?->id,
                    'listing_id'     => $validated['service_listing_id'],
                ]);

                SendJobReminderJob::dispatch($job->id, $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $job, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to create job'], 500);
            }
        }

        public function myJobs(): JsonResponse
        {
            try {
                $jobs = ServiceJob::where('client_id', $request->user()?->id)
                    ->orWhere('contractor_id', \App\Domains\HomeServices\Models\Contractor::where('user_id', $request->user()?->id)->value('id'))
                    ->with(['serviceListing', 'contractor', 'client'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $jobs, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to fetch jobs'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $job = ServiceJob::with(['serviceListing', 'contractor', 'client', 'reviews', 'disputes'])->findOrFail($id);
                $this->authorize('view', $job);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $job, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Job not found'], 404);
            }
        }

        public function accept(int $id): JsonResponse
        {
            try {
                $job = ServiceJob::findOrFail($id);
                $this->authorize('accept', $job);

                $correlationId = Str::uuid()->toString();
                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'job_accept', amount: 0, correlationId: $correlationId ?? '');
                $job->update(['status' => 'accepted', 'correlation_id' => $correlationId]);

                $this->logger->info('HomeService job accepted', [
                    'correlation_id' => $correlationId,
                    'job_id'         => $job->id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $job, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to accept job'], 500);
            }
        }

        public function start(int $id): JsonResponse
        {
            try {
                $job = ServiceJob::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'job_start', amount: 0, correlationId: $correlationId ?? '');
                $job->update(['status' => 'in_progress', 'started_at' => Carbon::now(), 'correlation_id' => $correlationId]);

                $this->logger->info('HomeService job started', [
                    'correlation_id' => $correlationId,
                    'job_id'         => $job->id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $job, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to start job'], 500);
            }
        }

        public function complete(int $id): JsonResponse
        {
            try {
                $job = ServiceJob::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(fn() => $this->jobService->completeJob($job, $correlationId));

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $job, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to complete job'], 500);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            try {
                $job = ServiceJob::findOrFail($id);
                $this->authorize('cancel', $job);

                $correlationId = Str::uuid()->toString();
                $this->db->transaction(fn() => $this->jobService->cancelJob($job, $request->input('reason', 'User cancelled'), $correlationId));

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Job cancelled', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to cancel job'], 500);
            }
        }

        public function createDispute(int $jobId): JsonResponse
        {
            try {
                $job = ServiceJob::findOrFail($jobId);
                $this->authorize('create', ServiceDispute::class);

                $validated = $request->validate([
                    'type' => 'required|in:quality_issue,incomplete_work,safety_concern,other',
                    'description' => 'required|string',
                    'evidence' => 'nullable|array',
                ]);

                $correlationId = Str::uuid()->toString();

                $dispute = ServiceDispute::create([
                    'tenant_id' => tenant()->id,
                    'job_id' => $jobId,
                    'initiator_id' => $request->user()?->id,
                    'type' => $validated['type'],
                    'description' => $validated['description'],
                    'evidence' => $validated['evidence'] ?? [],
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $dispute, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to create dispute'], 500);
            }
        }

        public function myDisputes(): JsonResponse
        {
            try {
                $disputes = ServiceDispute::where('initiator_id', $request->user()?->id)
                    ->with(['job'])
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $disputes, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to fetch disputes'], 500);
            }
        }

        public function resolveDispute(int $id): JsonResponse
        {
            try {
                $dispute = ServiceDispute::findOrFail($id);
                $this->authorize('resolve', $dispute);

                $validated = $request->validate([
                    'resolution' => 'required|string',
                    'refund_amount' => 'nullable|numeric|min:0',
                ]);

                $correlationId = Str::uuid()->toString();

                $dispute->update([
                    'status' => 'resolved',
                    'resolution' => $validated['resolution'],
                    'refund_amount' => $validated['refund_amount'] ?? null,
                    'resolved_by' => $request->user()->email,
                    'resolved_at' => Carbon::now(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $dispute, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to resolve dispute'], 500);
            }
        }

        public function resolve(int $id): JsonResponse
        {
            try {
                $job = ServiceJob::findOrFail($id);
                $this->authorize('view', $job);

                $validated = $request->validate(['status' => 'required|in:completed,cancelled']);

                $job->update(['status' => $validated['status']]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $job, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to resolve job'], 500);
            }
        }
}
