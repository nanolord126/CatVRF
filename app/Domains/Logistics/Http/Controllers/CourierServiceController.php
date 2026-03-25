<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use App\Domains\Logistics\Models\CourierService;
use App\Domains\Logistics\Services\CourierServiceService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CourierServiceController
{
    public function __construct(
        private readonly CourierServiceService $courierServiceService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $couriers = CourierService::where('is_verified', true)
                ->where('is_active', true)
                ->with('user')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $couriers,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to fetch couriers', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $courier = CourierService::with('user', 'shipments', 'ratings')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $courier, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Courier not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function register(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $courier = $this->courierServiceService->createCourierService(
                tenant('id'),
                auth()->id(),
                request('company_name'),
                request('license_number'),
                request('vehicle_types', []),
                request('service_radius'),
                request('base_rate'),
                request('per_km_rate'),
                $correlationId,
            );

            return response()->json(['success' => true, 'data' => $courier, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }

    public function myProfile(): JsonResponse
    {
        try {
            $courier = CourierService::where('user_id', auth()->id())->firstOrFail();
            return response()->json(['success' => true, 'data' => $courier, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Courier not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function updateProfile(): JsonResponse
    {
        try {
            $courier = CourierService::where('user_id', auth()->id())->firstOrFail();
            $correlationId = Str::uuid()->toString();

            $updated = $this->courierServiceService->updateCourierService(
                $courier,
                request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']),
                $correlationId,
            );

            return response()->json(['success' => true, 'data' => $updated, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function myShipments(): JsonResponse
    {
        try {
            $courier = CourierService::where('user_id', auth()->id())->firstOrFail();
            $shipments = $courier->shipments()->paginate(20);

            return response()->json(['success' => true, 'data' => $shipments, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function myEarnings(): JsonResponse
    {
        try {
            $courier = CourierService::where('user_id', auth()->id())->firstOrFail();
            $earnings = $courier->shipments()->where('status', 'delivered')->sum('shipping_cost');

            return response()->json(['success' => true, 'data' => ['total_earnings' => $earnings], 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function updateShipmentStatus(int $shipmentId): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($shipmentId, $correlationId) {
                $shipment = \App\Domains\Logistics\Models\Shipment::findOrFail($shipmentId);
                $shipment->update(['status' => request('status'), 'correlation_id' => $correlationId]);

                $this->log->channel('audit')->info('Shipment status updated', ['shipment_id' => $shipmentId, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function analytics(int $courierId): JsonResponse
    {
        try {
            $courier = CourierService::findOrFail($courierId);
            $shipmentCount = $courier->shipments()->count();
            $deliveredCount = $courier->shipments()->where('status', 'delivered')->count();
            $totalRevenue = $courier->shipments()->sum('shipping_cost');

            return response()->json([
                'success' => true,
                'data' => ['total_shipments' => $shipmentCount, 'delivered' => $deliveredCount, 'revenue' => $totalRevenue],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function verifyCourier(int $courierId): JsonResponse
    {
        try {
            $courier = CourierService::findOrFail($courierId);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($courier, $correlationId) {
                $courier->update(['is_verified' => true, 'correlation_id' => $correlationId]);
                $this->log->channel('audit')->info('Courier verified', ['courier_id' => $courier->id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $courier, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $courier = CourierService::findOrFail($id);

            $updated = $this->courierServiceService->updateCourierService(
                $courier,
                request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']),
                $correlationId,
            );

            return response()->json(['success' => true, 'data' => $updated, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $courier = CourierService::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($courier, $correlationId) {
                $courier->delete();
                $this->log->channel('audit')->info('Courier deleted', ['courier_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
