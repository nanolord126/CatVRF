<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class MeatProductController extends Controller
{

    public function __construct(private readonly MeatService $meatService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $tenantId = $request->user()?->tenant_id ?? 0;

                $products = MeatProduct::where('tenant_id', $tenantId)
                    ->when($request->input('type'),    fn ($q, $v) => $q->where('meat_type', $v))
                    ->when($request->input('cut'),     fn ($q, $v) => $q->where('cut_type', $v))
                    ->when($request->input('organic'), fn ($q, $v) => $q->where('is_organic', (bool) $v))
                    ->orderByDesc('created_at')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $products, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('MeatShops: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $product = MeatProduct::findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $product, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Товар не найден', 'correlation_id' => $correlationId], 404);
            }
        }

        public function order(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $userId = $request->user()?->id;

                $fraudResult = $this->fraud->check(
                    userId: $userId,
                    operationType: 'meat_order',
                    amount: (int) $request->input('total_kopecks', 0),
                    correlationId: $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
                }

                $validated = $request->validate([
                    'product_id'       => 'required|integer|exists:meat_products,id',
                    'weight_grams'     => 'required|integer|min:100',
                    'packaging'        => 'required|string|in:vacuum,regular,frozen',
                    'delivery_address' => 'required|string',
                    'delivery_date'    => 'required|date|after_or_equal:today',
                ]);

                $order = $this->db->transaction(function () use ($validated, $userId, $correlationId): MeatOrder {
                    $product = MeatProduct::findOrFail($validated['product_id']);
                    $pricePerGram = $product->price_per_100g / 100;
                    $order = MeatOrder::create([
                        'uuid'             => Str::uuid(),
                        'tenant_id'        => $request->user()?->tenant_id ?? 0,
                        'client_id'        => $userId,
                        'product_id'       => $validated['product_id'],
                        'weight_grams'     => $validated['weight_grams'],
                        'packaging'        => $validated['packaging'],
                        'delivery_address' => $validated['delivery_address'],
                        'delivery_date'    => $validated['delivery_date'],
                        'total_kopecks'    => (int) ($pricePerGram * $validated['weight_grams']),
                        'status'           => 'pending',
                        'correlation_id'   => $correlationId,
                    ]);

                    $this->logger->info('MeatShops: Order created', [
                        'order_id' => $order->id, 'user_id' => $userId, 'correlation_id' => $correlationId,
                    ]);

                    return $order;
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('MeatShops: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
            }
        }

        public function myOrders(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $orders = MeatOrder::where('client_id', $request->user()?->id)
                    ->with('product')
                    ->orderByDesc('created_at')
                    ->paginate(20);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }
}
