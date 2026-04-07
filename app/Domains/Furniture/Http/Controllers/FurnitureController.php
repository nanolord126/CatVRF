<?php declare(strict_types=1);

namespace App\Domains\Furniture\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FurnitureController extends Controller
{

    public function __construct(private readonly FurnitureService $furnitureService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $tenantId = $request->user()?->tenant_id ?? 0;

                $items = FurnitureItem::where('tenant_id', $tenantId)
                    ->when($request->input('style'),     fn ($q, $v) => $q->where('style', $v))
                    ->when($request->input('material'),  fn ($q, $v) => $q->where('material', $v))
                    ->when($request->input('room'),      fn ($q, $v) => $q->where('room_type', $v))
                    ->when($request->input('min_price'), fn ($q, $v) => $q->where('price', '>=', (int) $v))
                    ->when($request->input('max_price'), fn ($q, $v) => $q->where('price', '<=', (int) $v))
                    ->orderByDesc('rating')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $items, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Furniture: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $item = FurnitureItem::findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $item, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Товар не найден', 'correlation_id' => $correlationId], 404);
            }
        }

        public function view3D(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $item   = FurnitureItem::findOrFail($id);
                $model  = $item->model_3d_url ?? null;
                if ($model === null) {
                    return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => '3D-модель недоступна', 'correlation_id' => $correlationId], 404);
                }
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => ['model_url' => $model, 'item' => $item], 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка 3D', 'correlation_id' => $correlationId], 500);
            }
        }

        public function order(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $userId = $request->user()?->id;

                $fraudResult = $this->fraud->check(
                    userId: $userId,
                    operationType: 'furniture_order',
                    amount: (int) $request->input('total_kopecks', 0),
                    correlationId: $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
                }

                $validated = $request->validate([
                    'item_id'          => 'required|integer|exists:furniture_items,id',
                    'quantity'         => 'required|integer|min:1|max:50',
                    'delivery_address' => 'required|string',
                    'delivery_date'    => 'required|date|after:today',
                    'assembly'         => 'boolean',
                    'color'            => 'nullable|string',
                ]);

                $order = $this->db->transaction(function () use ($validated, $userId, $correlationId): FurnitureOrder {
                    $item  = FurnitureItem::findOrFail($validated['item_id']);
                    $order = FurnitureOrder::create([
                        'uuid'             => Str::uuid(),
                        'tenant_id'        => $request->user()?->tenant_id ?? 0,
                        'client_id'        => $userId,
                        'item_id'          => $validated['item_id'],
                        'quantity'         => $validated['quantity'],
                        'delivery_address' => $validated['delivery_address'],
                        'delivery_date'    => $validated['delivery_date'],
                        'assembly'         => $validated['assembly'] ?? false,
                        'color'            => $validated['color'] ?? null,
                        'total_kopecks'    => $item->price * $validated['quantity'],
                        'status'           => 'pending',
                        'correlation_id'   => $correlationId,
                    ]);

                    $this->logger->info('Furniture: Order created', [
                        'order_id' => $order->id, 'user_id' => $userId, 'correlation_id' => $correlationId,
                    ]);

                    return $order;
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('Furniture: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
            }
        }

        public function myOrders(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $orders = FurnitureOrder::where('client_id', $request->user()?->id)
                    ->with('item')
                    ->orderByDesc('created_at')
                    ->paginate(20);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }
}
