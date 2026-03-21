<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;

use App\Domains\Medical\Models\MedicalTestOrder;
use App\Domains\Medical\Services\TestOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MedicalTestOrderController
{
    public function __construct(
        private readonly TestOrderService $testOrderService,
    ) {}

    public function myTestOrders(): JsonResponse
    {
        try {
            $orders = MedicalTestOrder::where('patient_id', auth()->user()->id)
                ->orderBy('ordered_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to fetch test orders'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid();

            $testOrder = DB::transaction(function () use ($request, $correlationId) {
                return $this->testOrderService->createTestOrder(
                    tenantId: auth()->user()->tenant_id,
                    appointmentId: $request->input('appointment_id'),
                    patientId: auth()->user()->id,
                    clinicId: $request->input('clinic_id'),
                    tests: $request->input('tests', []),
                    totalAmount: $request->input('total_amount'),
                    correlationId: $correlationId,
                );
            });

            return response()->json([
                'success' => true,
                'data' => $testOrder,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to create test order', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to create test order'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $testOrder = MedicalTestOrder::findOrFail($id);

            $this->authorize('view', $testOrder);

            return response()->json([
                'success' => true,
                'data' => $testOrder,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Test order not found'], 404);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $testOrder = MedicalTestOrder::findOrFail($id);

            $testOrder->update(['status' => 'cancelled']);

            Log::channel('audit')->info('Test order cancelled', ['test_order_id' => $testOrder->id]);

            return response()->json(['success' => true, 'data' => $testOrder]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Cancel failed'], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $orders = MedicalTestOrder::paginate(50);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to fetch test orders'], 500);
        }
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        try {
            $testOrder = MedicalTestOrder::findOrFail($id);

            $testOrder = $this->testOrderService->completeTestOrder(
                testOrder: $testOrder,
                results: $request->input('results', []),
                correlationId: $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            );

            return response()->json(['success' => true, 'data' => $testOrder]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Complete failed'], 500);
        }
    }

    public function analytics(): JsonResponse
    {
        try {
            $orders = MedicalTestOrder::where('status', 'completed')->get();

            $analytics = [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('total_amount'),
                'total_commission' => $orders->sum('commission_amount'),
                'average_order_value' => $orders->avg('total_amount'),
                'by_status' => MedicalTestOrder::groupBy('status')->selectRaw('status, count(*) as count')->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Analytics failed'], 500);
        }
    }
}
