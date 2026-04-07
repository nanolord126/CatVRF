<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ShipmentController extends Controller
{

    public function __construct(private readonly ShipmentService $shipmentService,
            private readonly TrackingService $trackingService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {

                $shipment = $this->shipmentService->createShipment(
                    tenant()?->id,
                    $request->input('courier_service_id'),
                    $request->user()?->id,
                    $request->input('origin_address'),
                    $request->input('destination_address'),
                    $request->input('weight'),
                    $request->input('declared_value'),
                    $request->input('shipping_cost'),
                    $correlationId,
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $shipment, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create shipment', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function myShipments(): JsonResponse
        {
            try {
                $shipments = Shipment::where('customer_id', $request->user()?->id)
                    ->with('courierService')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $shipments, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $shipment = Shipment::with('courierService', 'tracking', 'ratings')->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $shipment, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Shipment not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $shipment = Shipment::findOrFail($id);

                $this->db->transaction(function () use ($shipment, $correlationId) {
                    $shipment->update(['correlation_id' => $correlationId]);
                    $this->logger->info('Shipment updated', ['shipment_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $shipment, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            try {
                $shipment = Shipment::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->shipmentService->cancelShipment($shipment, $request->input('reason'), $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function trackByNumber(string $trackingNumber): JsonResponse
        {
            try {
                $shipment = Shipment::where('tracking_number', $trackingNumber)->with('tracking')->firstOrFail();
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $shipment, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Shipment not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $shipments = Shipment::with('courierService', 'customer')->paginate(50);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $shipments, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function updateStatus(int $id): JsonResponse
        {
            try {
                $shipment = Shipment::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->shipmentService->updateShipmentStatus($shipment, $request->input('status'), $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $shipment, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $totalShipments = Shipment::count();
                $deliveredShipments = Shipment::where('status', 'delivered')->count();
                $totalRevenue = Shipment::sum('shipping_cost');

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => ['total' => $totalShipments, 'delivered' => $deliveredShipments, 'revenue' => $totalRevenue],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
