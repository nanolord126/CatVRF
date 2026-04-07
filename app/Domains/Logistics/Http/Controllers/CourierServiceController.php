<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class CourierServiceController extends Controller
{

    public function __construct(private readonly CourierServiceService $courierServiceService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $couriers = CourierService::where('is_verified', true)
                    ->where('is_active', true)
                    ->with('user')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $couriers,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch couriers', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $courier = CourierService::with('user', 'shipments', 'ratings')->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $courier, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Courier not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function register(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $courier = $this->courierServiceService->createCourierService(
                    tenant()?->id,
                    $request->user()?->id,
                    $request->input('company_name'),
                    $request->input('license_number'),
                    $request->input('vehicle_types', []),
                    $request->input('service_radius'),
                    $request->input('base_rate'),
                    $request->input('per_km_rate'),
                    $correlationId,
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $courier, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function myProfile(): JsonResponse
        {
            try {
                $courier = CourierService::where('user_id', $request->user()?->id)->firstOrFail();
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $courier, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Courier not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function updateProfile(): JsonResponse
        {
            try {
                $courier = CourierService::where('user_id', $request->user()?->id)->firstOrFail();
                $correlationId = Str::uuid()->toString();

                $updated = $this->courierServiceService->updateCourierService(
                    $courier,
                    $request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']),
                    $correlationId,
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $updated, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function myShipments(): JsonResponse
        {
            try {
                $courier = CourierService::where('user_id', $request->user()?->id)->firstOrFail();
                $shipments = $courier->shipments()->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $shipments, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function myEarnings(): JsonResponse
        {
            try {
                $courier = CourierService::where('user_id', $request->user()?->id)->firstOrFail();
                $earnings = $courier->shipments()->where('status', 'delivered')->sum('shipping_cost');

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => ['total_earnings' => $earnings], 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function updateShipmentStatus(int $shipmentId): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($shipmentId, $correlationId) {
                    $shipment = \App\Domains\Logistics\Models\Shipment::findOrFail($shipmentId);
                    $shipment->update(['status' => $request->input('status'), 'correlation_id' => $correlationId]);

                    $this->logger->info('Shipment status updated', ['shipment_id' => $shipmentId, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function analytics(int $courierId): JsonResponse
        {
            try {
                $courier = CourierService::findOrFail($courierId);
                $shipmentCount = $courier->shipments()->count();
                $deliveredCount = $courier->shipments()->where('status', 'delivered')->count();
                $totalRevenue = $courier->shipments()->sum('shipping_cost');

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => ['total_shipments' => $shipmentCount, 'delivered' => $deliveredCount, 'revenue' => $totalRevenue],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function verifyCourier(int $courierId): JsonResponse
        {
            try {
                $courier = CourierService::findOrFail($courierId);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($courier, $correlationId) {
                    $courier->update(['is_verified' => true, 'correlation_id' => $correlationId]);
                    $this->logger->info('Courier verified', ['courier_id' => $courier->id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $courier, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $courier = CourierService::findOrFail($id);

                $updated = $this->courierServiceService->updateCourierService(
                    $courier,
                    $request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']),
                    $correlationId,
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $updated, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $courier = CourierService::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($courier, $correlationId) {
                    $courier->delete();
                    $this->logger->info('Courier deleted', ['courier_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
