<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalTestOrderController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly TestOrderService $testOrderService,
            private readonly FraudControlService $fraudControlService,
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
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {

                $validated = $request->all();
                $testOrder = DB::transaction(function () use ($validated, $correlationId) {
                    return $this->testOrderService->createTestOrder(
                        tenantId: auth()->user()->tenant_id,
                        appointmentId: ($validated['appointment_id'] ?? null),
                        patientId: auth()->user()->id,
                        clinicId: ($validated['clinic_id'] ?? null),
                        tests: ($validated['tests'] ?? []),
                        totalAmount: ($validated['total_amount'] ?? null),
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
