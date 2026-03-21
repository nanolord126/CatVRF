<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Models\AutoServiceOrder;
use App\Domains\Auto\Models\AutoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления заказами автосервиса.
 * Production 2026.
 */
final class AutoServiceOrderController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $orders = AutoServiceOrder::query()
                ->where('tenant_id', tenant('id') ?? 1)
                ->with(['service', 'client'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении заказов',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid()->toString();

            $request->validate([
                'client_id' => 'required|exists:users,id',
                'car_brand' => 'required|string',
                'car_model' => 'required|string',
                'service_id' => 'nullable|exists:auto_services,id',
                'appointment_datetime' => 'required|date_format:Y-m-d H:i:s',
            ]);

            $order = DB::transaction(function () use ($request, $correlationId) {
                $order = AutoServiceOrder::create([
                    'tenant_id' => tenant('id') ?? 1,
                    'client_id' => $request->get('client_id'),
                    'car_brand' => $request->get('car_brand'),
                    'car_model' => $request->get('car_model'),
                    'service_id' => $request->get('service_id'),
                    'appointment_datetime' => $request->get('appointment_datetime'),
                    'status' => 'pending',
                    'total_price' => 0, 
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Service order created', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заказа',
            ], 500);
        }
    }

    public function show(AutoServiceOrder $order): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $order->load(['service', 'client']),
        ]);
    }

    public function cancel(AutoServiceOrder $order): JsonResponse
    {
        try {
            $this->authorize('cancel', $order);

            $order->update(['status' => 'cancelled']);

            Log::channel('audit')->info('Service order cancelled', [
                'order_id' => $order->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заказ отменён',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене заказа',
            ], 500);
        }
    }

    public function complete(AutoServiceOrder $order): JsonResponse
    {
        try {
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::channel('audit')->info('Service order completed', [
                'order_id' => $order->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заказ завершён',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при завершении заказа',
            ], 500);
        }
    }

    public function listServices(Request $request): JsonResponse
    {
        $services = AutoService::query()
            ->where('tenant_id', tenant('id') ?? 1)
            ->select(['id', 'name', 'price', 'duration_minutes'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }
}
