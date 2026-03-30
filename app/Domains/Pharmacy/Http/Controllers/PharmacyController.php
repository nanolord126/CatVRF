<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PharmacyController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly PharmacyService $pharmacyService,
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $tenantId = auth()->user()?->tenant_id ?? 0;

                $medicines = Medicine::where('tenant_id', $tenantId)
                    ->when($request->input('prescription'), fn ($q, $v) => $q->where('requires_prescription', (bool) $v))
                    ->when($request->input('category'),     fn ($q, $v) => $q->where('category', $v))
                    ->when($request->input('search'),       fn ($q, $v) => $q->where(function ($q2) use ($v) {
                        $q2->where('name', 'like', "%{$v}%")->orWhere('active_substance', 'like', "%{$v}%");
                    }))
                    ->orderBy('name')
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $medicines, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Pharmacy: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $medicine = Medicine::findOrFail($id);
                return response()->json(['success' => true, 'data' => $medicine, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Препарат не найден', 'correlation_id' => $correlationId], 404);
            }
        }

        public function order(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $userId = auth()->id();

                $fraudResult = $this->fraudControlService->check(
                    userId: $userId,
                    operationType: 'pharmacy_order',
                    amount: (int) $request->input('total_kopecks', 0),
                    correlationId: $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    return response()->json(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
                }

                $validated = $request->validate([
                    'items'             => 'required|array|min:1',
                    'items.*.medicine_id' => 'required|integer|exists:medicines,id',
                    'items.*.quantity'  => 'required|integer|min:1',
                    'prescription_url'  => 'nullable|url',
                    'delivery_address'  => 'required|string',
                ]);

                $order = DB::transaction(function () use ($validated, $userId, $correlationId): PharmacyOrder {
                    $totalKopecks = 0;
                    foreach ($validated['items'] as $item) {
                        $med = Medicine::findOrFail($item['medicine_id']);
                        if ($med->requires_prescription && empty($validated['prescription_url'])) {
                            throw new \RuntimeException("Требуется рецепт для препарата: {$med->name}");
                        }
                        $totalKopecks += $med->price * $item['quantity'];
                    }

                    $order = PharmacyOrder::create([
                        'uuid'             => Str::uuid(),
                        'tenant_id'        => auth()->user()?->tenant_id ?? 0,
                        'client_id'        => $userId,
                        'items_json'       => $validated['items'],
                        'prescription_url' => $validated['prescription_url'] ?? null,
                        'delivery_address' => $validated['delivery_address'],
                        'total_kopecks'    => $totalKopecks,
                        'status'           => 'pending',
                        'correlation_id'   => $correlationId,
                    ]);

                    Log::channel('audit')->info('Pharmacy: Order created', [
                        'order_id' => $order->id, 'user_id' => $userId, 'correlation_id' => $correlationId,
                    ]);

                    return $order;
                });

                return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\RuntimeException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Pharmacy: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
            }
        }

        public function myOrders(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $orders = PharmacyOrder::where('client_id', auth()->id())
                    ->orderByDesc('created_at')
                    ->paginate(20);
                return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }
}
