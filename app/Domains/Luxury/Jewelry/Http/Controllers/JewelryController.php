<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class JewelryController extends Controller
{

    public function __construct(private readonly JewelryService $jewelryService,
            private readonly CertificateService $certificateService,
            private readonly Jewelry3DService $jewelry3DService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $tenantId = $request->user()?->tenant_id ?? 0;

                $items = JewelryItem::where('tenant_id', $tenantId)
                    ->when($request->input('metal'),    fn ($q, $v) => $q->where('metal', $v))
                    ->when($request->input('gem'),      fn ($q, $v) => $q->where('gem', $v))
                    ->when($request->input('category'), fn ($q, $v) => $q->where('category', $v))
                    ->when($request->input('min_price'), fn ($q, $v) => $q->where('price', '>=', (int) $v))
                    ->when($request->input('max_price'), fn ($q, $v) => $q->where('price', '<=', (int) $v))
                    ->orderByDesc('created_at')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $items, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Jewelry: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $item = JewelryItem::findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $item, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Изделие не найдено', 'correlation_id' => $correlationId], 404);
            }
        }

        public function view3D(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $item    = JewelryItem::findOrFail($id);
                $model3d = $this->jewelry3DService->getModel($item, $correlationId);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $model3d, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Jewelry: 3D view error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка 3D-просмотра', 'correlation_id' => $correlationId], 500);
            }
        }

        public function certificate(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $item = JewelryItem::findOrFail($id);
                $cert = $this->certificateService->get($item, $correlationId);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $cert, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Сертификат не найден', 'correlation_id' => $correlationId], 404);
            }
        }

        public function order(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $userId = $request->user()?->id;

                $fraudResult = $this->fraud->check(
                    userId: $userId,
                    operationType: 'jewelry_order',
                    amount: (int) $request->input('price_kopecks', 0),
                    correlationId: $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
                }

                $validated = $request->validate([
                    'item_id'          => 'required|integer|exists:jewelry_items,id',
                    'size'             => 'nullable|string',
                    'engraving'        => 'nullable|string|max:100',
                    'gift_wrapping'    => 'boolean',
                    'delivery_address' => 'required|string',
                ]);

                $order = $this->db->transaction(function () use ($validated, $userId, $correlationId): JewelryOrder {
                    $item  = JewelryItem::findOrFail($validated['item_id']);
                    $order = JewelryOrder::create([
                        'uuid'             => Str::uuid(),
                        'tenant_id'        => $request->user()?->tenant_id ?? 0,
                        'client_id'        => $userId,
                        'item_id'          => $validated['item_id'],
                        'size'             => $validated['size'] ?? null,
                        'engraving'        => $validated['engraving'] ?? null,
                        'gift_wrapping'    => $validated['gift_wrapping'] ?? false,
                        'delivery_address' => $validated['delivery_address'],
                        'price'            => $item->price,
                        'status'           => 'pending',
                        'correlation_id'   => $correlationId,
                    ]);

                    $this->logger->info('Jewelry: Order created', [
                        'order_id'       => $order->id,
                        'item_id'        => $validated['item_id'],
                        'user_id'        => $userId,
                        'correlation_id' => $correlationId,
                    ]);

                    return $order;
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('Jewelry: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
            }
        }

        public function myOrders(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $orders = JewelryOrder::where('client_id', $request->user()?->id)
                    ->with('item')
                    ->orderByDesc('created_at')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }
}
