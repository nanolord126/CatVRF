<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\FreshProduce\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ProduceOrderController extends Controller
{

    public function __construct(private readonly FreshProduceService $freshProduceService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $orders = ProduceOrder::where('client_id', $request->user()?->id)
                    ->with('box')
                    ->orderByDesc('created_at')
                    ->paginate(20);

                $this->logger->info('FreshProduce: orders list', [
                    'user_id'        => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('FreshProduce: orders index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $userId = $request->user()?->id;

                $fraudResult = $this->fraud->check(
                    userId: $userId,
                    operationType: 'fresh_produce_order',
                    amount: (int) $request->input('price_kopecks', 0),
                    correlationId: $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
                }

                $validated = $request->validate([
                    'box_id'           => 'required|integer',
                    'delivery_address' => 'required|string|max:500',
                    'delivery_date'    => 'required|date|after:today',
                    'delivery_slot'    => 'required|string',
                    'subscription_id'  => 'nullable|integer',
                ]);

                $order = $this->freshProduceService->placeOrder(
                    clientId:       $userId,
                    boxId:          $validated['box_id'],
                    deliveryAddress: $validated['delivery_address'],
                    deliveryDate:   $validated['delivery_date'],
                    deliverySlot:   $validated['delivery_slot'],
                    subscriptionId: $validated['subscription_id'] ?? null,
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('FreshProduce: store error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка оформления заказа', 'correlation_id' => $correlationId], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $order = ProduceOrder::where('client_id', $request->user()?->id)->with('box')->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Заказ не найден', 'correlation_id' => $correlationId], 404);
            }
        }

        public function cancel(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $order = ProduceOrder::where('client_id', $request->user()?->id)->findOrFail($id);

                $this->db->transaction(function () use ($order, $correlationId): void {
                    $order->update(['status' => 'cancelled', 'correlation_id' => $correlationId]);

                    $this->logger->info('FreshProduce: Order cancelled', [
                        'order_id'       => $order->id,
                        'user_id'        => $request->user()?->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Заказ отменён', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('FreshProduce: cancel error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка отмены', 'correlation_id' => $correlationId], 500);
            }
        }

        // ────────────────────────── Подписки ──────────────────────────────────────

        public function subscriptions(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $subs = ProduceSubscription::where('client_id', $request->user()?->id)
                    ->with('box')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $subs, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }

        public function subscribe(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $userId = $request->user()?->id;

                $fraudResult = $this->fraud->check(
                    userId: $userId,
                    operationType: 'fresh_produce_subscribe',
                    amount: 0,
                    correlationId: $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
                }

                $validated = $request->validate([
                    'box_id'           => 'required|integer',
                    'delivery_address' => 'required|string|max:500',
                    'delivery_day'     => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                    'delivery_slot'    => 'required|string',
                ]);

                $sub = $this->db->transaction(function () use ($validated, $userId, $correlationId): ProduceSubscription {
                    $existing = ProduceSubscription::where('client_id', $userId)
                        ->where('box_id', $validated['box_id'])
                        ->where('status', 'active')
                        ->first();

                    if ($existing) {
                        throw new \RuntimeException('Подписка на этот бокс уже активна');
                    }

                    $sub = ProduceSubscription::create([
                        'uuid'             => Str::uuid(),
                        'client_id'        => $userId,
                        'box_id'           => $validated['box_id'],
                        'delivery_address' => $validated['delivery_address'],
                        'delivery_day'     => $validated['delivery_day'],
                        'delivery_slot'    => $validated['delivery_slot'],
                        'status'           => 'active',
                        'correlation_id'   => $correlationId,
                    ]);

                    $this->logger->info('FreshProduce: Subscription created', [
                        'subscription_id' => $sub->id,
                        'user_id'         => $userId,
                        'correlation_id'  => $correlationId,
                    ]);

                    return $sub;
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $sub, 'correlation_id' => $correlationId], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\RuntimeException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => $correlationId], 409);
            } catch (\Throwable $e) {
                $this->logger->error('FreshProduce: subscribe error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка подписки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function unsubscribe(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $sub = ProduceSubscription::where('client_id', $request->user()?->id)->findOrFail($id);

                $this->db->transaction(function () use ($sub, $correlationId): void {
                    $sub->update(['status' => 'cancelled', 'correlation_id' => $correlationId]);
                    $this->logger->info('FreshProduce: Subscription cancelled', ['sub_id' => $sub->id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Подписка отменена', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('FreshProduce: unsubscribe error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }
}
