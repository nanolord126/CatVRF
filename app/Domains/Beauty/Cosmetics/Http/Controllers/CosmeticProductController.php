<?php declare(strict_types=1);

namespace App\Domains\Beauty\Cosmetics\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CosmeticProductController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly CosmeticService $cosmeticService,
            private readonly BeautyTryOnService $tryOnService,
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $tenantId = auth()->user()?->tenant_id ?? 0;

                $products = CosmeticProduct::where('tenant_id', $tenantId)
                    ->when($request->input('brand'),     fn ($q, $v) => $q->where('brand', $v))
                    ->when($request->input('category'),  fn ($q, $v) => $q->where('category', $v))
                    ->when($request->input('skin_type'), fn ($q, $v) => $q->whereJsonContains('skin_types', $v))
                    ->when($request->input('search'),    fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                    ->orderByDesc('rating')
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $products, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Cosmetics: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $product = CosmeticProduct::findOrFail($id);
                return response()->json(['success' => true, 'data' => $product, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Продукт не найден', 'correlation_id' => $correlationId], 404);
            }
        }

        public function tryOn(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $product = CosmeticProduct::findOrFail($id);

                $validated = $request->validate([
                    'photo_url'  => 'nullable|url',
                    'shade'      => 'nullable|string',
                ]);

                $result = $this->tryOnService->tryOn($product, $validated['photo_url'] ?? null, $validated['shade'] ?? null, $correlationId);

                return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Cosmetics: tryOn error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => 'Ошибка AR-примерки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function order(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $userId = auth()->id();

                $fraudResult = $this->fraudControlService->check(
                    userId: $userId,
                    operationType: 'cosmetic_order',
                    amount: (int) $request->input('total_kopecks', 0),
                    correlationId: $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    return response()->json(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
                }

                $validated = $request->validate([
                    'product_id'       => 'required|integer|exists:cosmetic_products,id',
                    'quantity'         => 'required|integer|min:1|max:50',
                    'delivery_address' => 'required|string',
                    'shade'            => 'nullable|string',
                ]);

                $order = DB::transaction(function () use ($validated, $userId, $correlationId): CosmeticOrder {
                    $product = CosmeticProduct::findOrFail($validated['product_id']);
                    $order   = CosmeticOrder::create([
                        'uuid'             => Str::uuid(),
                        'tenant_id'        => auth()->user()?->tenant_id ?? 0,
                        'client_id'        => $userId,
                        'product_id'       => $validated['product_id'],
                        'quantity'         => $validated['quantity'],
                        'shade'            => $validated['shade'] ?? null,
                        'delivery_address' => $validated['delivery_address'],
                        'total_kopecks'    => $product->price * $validated['quantity'],
                        'status'           => 'pending',
                        'correlation_id'   => $correlationId,
                    ]);

                    Log::channel('audit')->info('Cosmetics: Order created', [
                        'order_id'       => $order->id,
                        'product_id'     => $validated['product_id'],
                        'user_id'        => $userId,
                        'correlation_id' => $correlationId,
                    ]);

                    return $order;
                });

                return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Cosmetics: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
            }
        }

        public function myOrders(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $orders = CosmeticOrder::where('client_id', auth()->id())
                    ->with('product')
                    ->orderByDesc('created_at')
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }
}
