<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionRetailOrderController extends Controller
{

    public function __construct(private readonly OrderService $orderService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $orders = FashionRetailOrder::where('user_id', $request->user()?->id)
                    ->orWhere('user_id', $request->user()?->id)
                    ->with('shop', 'user')
                    ->paginate(20);

                $correlationId = Str::uuid()->toString();
                $this->logger->info('FashionRetail orders listed', [
                    'count' => $orders->count(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $orders,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail order listing failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $order = FashionRetailOrder::with('shop', 'user', 'returns')->findOrFail($id);

                if ($order->user_id !== $request->user()?->id) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Unauthorized',
                        'correlation_id' => Str::uuid(),
                    ], 403);
                }

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $order,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Order not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $order = $this->db->transaction(function () use ($correlationId) {
                    return FashionRetailOrder::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => tenant()->id,
                        'business_group_id' => tenant()->business_group_id,
                        'user_id' => $request->user()?->id,
                        'shop_id' => $request->input('shop_id'),
                        'order_number' => 'FRO-' . date('YmdHis') . Str::random(4),
                        'items' => $request->input('items', []),
                        'total_amount' => $request->input('total_amount'),
                        'discount_amount' => $request->input('discount_amount', 0),
                        'commission_amount' => $request->input('commission_amount', 0),
                        'delivery_fee' => $request->input('delivery_fee', 0),
                        'status' => 'pending',
                        'payment_status' => 'pending',
                        'delivery_address' => $request->input('delivery_address'),
                        'delivery_method' => $request->input('delivery_method', 'standard'),
                        'notes' => $request->input('notes'),
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('FashionRetail order created', [
                    'order_id' => $order->id,
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $order,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail order creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function updateStatus(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $order = FashionRetailOrder::findOrFail($id);

                $this->db->transaction(function () use ($order, $correlationId) {
                    $order->update([
                        'status' => $request->input('status'),
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('FashionRetail order status updated', [
                    'order_id' => $id,
                    'status' => $request->input('status'),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $order,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail order status update failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
