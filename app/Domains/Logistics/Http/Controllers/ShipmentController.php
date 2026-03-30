<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly ShipmentService $shipmentService,
            private readonly TrackingService $trackingService,
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {

                $shipment = $this->shipmentService->createShipment(
                    tenant('id'),
                    request('courier_service_id'),
                    auth()->id(),
                    request('origin_address'),
                    request('destination_address'),
                    request('weight'),
                    request('declared_value'),
                    request('shipping_cost'),
                    $correlationId,
                );

                return response()->json(['success' => true, 'data' => $shipment, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to create shipment', ['error' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function myShipments(): JsonResponse
        {
            try {
                $shipments = Shipment::where('customer_id', auth()->id())
                    ->with('courierService')
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $shipments, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $shipment = Shipment::with('courierService', 'tracking', 'ratings')->findOrFail($id);
                return response()->json(['success' => true, 'data' => $shipment, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Shipment not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $shipment = Shipment::findOrFail($id);

                DB::transaction(function () use ($shipment, $correlationId) {
                    $shipment->update(['correlation_id' => $correlationId]);
                    Log::channel('audit')->info('Shipment updated', ['shipment_id' => $id, 'correlation_id' => $correlationId]);
                });

                return response()->json(['success' => true, 'data' => $shipment, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            try {
                $shipment = Shipment::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->shipmentService->cancelShipment($shipment, request('reason'), $correlationId);

                return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function trackByNumber(string $trackingNumber): JsonResponse
        {
            try {
                $shipment = Shipment::where('tracking_number', $trackingNumber)->with('tracking')->firstOrFail();
                return response()->json(['success' => true, 'data' => $shipment, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Shipment not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $shipments = Shipment::with('courierService', 'customer')->paginate(50);
                return response()->json(['success' => true, 'data' => $shipments, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function updateStatus(int $id): JsonResponse
        {
            try {
                $shipment = Shipment::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->shipmentService->updateShipmentStatus($shipment, request('status'), $correlationId);

                return response()->json(['success' => true, 'data' => $shipment, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $totalShipments = Shipment::count();
                $deliveredShipments = Shipment::where('status', 'delivered')->count();
                $totalRevenue = Shipment::sum('shipping_cost');

                return response()->json([
                    'success' => true,
                    'data' => ['total' => $totalShipments, 'delivered' => $deliveredShipments, 'revenue' => $totalRevenue],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
