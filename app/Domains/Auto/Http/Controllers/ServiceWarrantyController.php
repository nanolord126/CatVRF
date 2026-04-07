<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;



use App\Services\FraudControlService;
use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ServiceWarrantyController extends Controller
{

    public function __construct(
        private readonly FraudControlService $fraud,
            private readonly WarrantyService $warrantyService, private readonly LoggerInterface $logger
        ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $warranties = ServiceWarranty::query()
                    ->when($request->status, fn($q) => $q->where('status', $request->status))
                    ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
                    ->when($request->claim_status, fn($q) => $q->where('claim_status', $request->claim_status))
                    ->with(['serviceOrder', 'client', 'vehicle'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $warranties,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Service warranty index failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to retrieve service warranties',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
        $this->fraud->check(new \App\DTOs\OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()));

            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'auto_service_order_id' => 'required|exists:auto_service_orders,id',
                'client_id' => 'required|exists:users,id',
                'vehicle_id' => 'required|exists:vehicles,id',
                'warranty_type' => 'required|in:standard,extended,lifetime',
                'warranty_months' => 'required|integer|min:1|max:60',
                'warranty_km' => 'nullable|integer|min:1000',
                'start_date' => 'required|date',
                'start_mileage' => 'required|integer|min:0',
                'repair_description' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            try {
                $warranty = $this->warrantyService->createServiceWarranty($validated);

                $this->logger->info('Service warranty created', [
                    'correlation_id' => $correlationId,
                    'warranty_id' => $warranty->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $warranty->load(['serviceOrder', 'client', 'vehicle']),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Service warranty creation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create service warranty',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function show(ServiceWarranty $warranty): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $warranty->load(['serviceOrder', 'client', 'vehicle']),
            ]);
        }

        public function claim(Request $request, ServiceWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'claim_reason' => 'required|string',
                'claim_mileage' => 'required|integer|min:0',
                'notes' => 'nullable|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->submitServiceWarrantyClaim(
                    $warranty->id,
                    $validated['claim_reason'],
                    $validated['claim_mileage'],
                    $validated['notes'] ?? null
                );

                $this->logger->info('Service warranty claim submitted', [
                    'correlation_id' => $correlationId,
                    'warranty_id' => $warranty->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['serviceOrder', 'client', 'vehicle']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Service warranty claim failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }

        public function approve(Request $request, ServiceWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'notes' => 'nullable|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->approveServiceWarrantyClaim(
                    $warranty->id,
                    $validated['notes'] ?? null
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['serviceOrder', 'client', 'vehicle']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Service warranty approval failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to approve warranty claim',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function reject(Request $request, ServiceWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'notes' => 'required|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->rejectServiceWarrantyClaim(
                    $warranty->id,
                    $validated['notes']
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['serviceOrder', 'client', 'vehicle']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Service warranty rejection failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to reject warranty claim',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
