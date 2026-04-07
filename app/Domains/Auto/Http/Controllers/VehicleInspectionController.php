<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class VehicleInspectionController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $inspections = VehicleInspection::query()
                    ->when($request->status, fn($q) => $q->where('status', $request->status))
                    ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
                    ->with(['client', 'vehicle', 'inspector'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $inspections,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle inspection index failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to retrieve vehicle inspections',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'vehicle_id' => 'required|exists:vehicles,id',
                'client_id' => 'required|exists:users,id',
                'inspection_type' => 'required|in:periodic,pre_purchase,insurance,custom',
                'scheduled_at' => 'required|date|after:now',
                'inspector_id' => 'required|exists:users,id',
                'price' => 'required|integer|min:0',
                'notes' => 'nullable|string',
            ]);

            try {
                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'amount', amount: 0, correlationId: $correlationId ?? '');

                $inspection = $this->db->transaction(function () use ($validated, $correlationId) {
                    return VehicleInspection::create([
                        ...$validated,
                        'tenant_id' => tenant()->id,
                        'status' => 'scheduled',
                        'payment_status' => 'pending',
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('Vehicle inspection created', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $inspection->load(['client', 'vehicle', 'inspector']),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle inspection creation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create vehicle inspection',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function show(VehicleInspection $inspection): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $inspection->load(['client', 'vehicle', 'inspector']),
            ]);
        }

        public function pass(VehicleInspection $inspection): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $this->db->transaction(function () use ($inspection) {
                    $inspection->update([
                        'status' => 'completed',
                        'result' => 'passed',
                        'completed_at' => Carbon::now(),
                        'expires_at' => Carbon::now()->addMonths(12),
                        'certificate_number' => 'CERT-' . Str::uuid()->toString(),
                    ]);
                });

                $this->logger->info('Vehicle inspection passed', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $inspection->fresh(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle inspection pass failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to pass vehicle inspection',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function fail(VehicleInspection $inspection, Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'notes' => 'required|string',
            ]);

            try {
                $this->db->transaction(function () use ($inspection, $validated) {
                    $inspection->update([
                        'status' => 'completed',
                        'result' => 'failed',
                        'completed_at' => Carbon::now(),
                        'notes' => $validated['notes'],
                    ]);
                });

                $this->logger->info('Vehicle inspection failed', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $inspection->fresh(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle inspection fail recording failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to record vehicle inspection failure',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function update(Request $request, VehicleInspection $inspection): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'scheduled_at' => 'sometimes|date|after:now',
                'inspector_id' => 'sometimes|exists:users,id',
                'notes' => 'nullable|string',
            ]);

            try {
                $this->db->transaction(function () use ($inspection, $validated) {
                    $inspection->update($validated);
                });

                $this->logger->info('Vehicle inspection updated', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $inspection->fresh(['client', 'vehicle', 'inspector']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle inspection update failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update vehicle inspection',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function destroy(VehicleInspection $inspection): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $inspection->delete();

                $this->logger->info('Vehicle inspection deleted', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Vehicle inspection deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle inspection deletion failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete vehicle inspection',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
