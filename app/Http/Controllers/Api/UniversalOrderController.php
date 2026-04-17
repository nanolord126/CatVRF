<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Str;

use App\Services\FraudControlService;
use App\Services\Wallet\WalletService;
use App\Services\Security\IdempotencyService;
use App\Services\CommissionService;
use App\Services\NotificationService;
use App\Jobs\ProcessB2BOrderJob;

use App\Models\Order;
use App\Models\OrderItem;

final class UniversalOrderController
{
    public function __construct(
        private readonly Request $request,
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly IdempotencyService $idempotency,
        private readonly CommissionService $commission,
        private readonly NotificationService $notification,
        private readonly DatabaseManager $db,
        private readonly Dispatcher $bus,
    ) {}

    private function getCorrelationId(): string
    {
        return $this->request->get('correlation_id')
            ?? $this->request->header('X-Correlation-ID')
            ?? Str::uuid()->toString();
    }

    private function isB2B(): bool
    {
        return $this->request->get('b2b_mode') === true;
    }

    private function auditLog(string $action, array $data = []): void
    {
        $this->logger->channel('audit')->info($action, array_merge([
            'correlation_id' => $this->getCorrelationId(),
            'user_id' => $this->guard->id(),
            'ip_address' => $this->request->ip(),
            'mode' => $this->isB2B() ? 'b2b' : 'b2c',
        ], $data));
    }

    private function successResponse(mixed $data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return $this->response->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'correlation_id' => $this->getCorrelationId(),
        ], $code);
    }

    private function errorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'correlation_id' => $this->getCorrelationId(),
        ], $code);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $correlationId = $this->getCorrelationId();
        $tenantId = $this->guard->id() ?? 0;
        $vertical = $request->input('vertical', 'marketplace');
        $isB2B = $this->isB2B();

        try {
            $this->auditLog('Order creation initiated', [
                'vertical' => $vertical,
                'is_b2b' => $isB2B,
            ]);

            $validated = $request->validate([
                'vertical' => 'required|string|max:50',
                'items' => 'required|array|min:1',
                'items.*.product_type' => 'required|string|max:100',
                'items.*.product_id' => 'required|integer',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|integer|min:0',
                'items.*.options' => 'nullable|array',
                'delivery_address' => 'nullable|string|max:500',
                'delivery_lat' => 'nullable|numeric|between:-90,90',
                'delivery_lon' => 'nullable|numeric|between:-180,180',
                'payment_method' => 'required|string|in:card,sbp,wallet,b2b_credit',
                'idempotency_key' => 'required|string|max:255',
                'inn' => 'nullable|string|max:12|required_if:is_b2b,true',
                'business_card_id' => 'nullable|string|max:100',
            ]);

            $idempotencyKey = $validated['idempotency_key'];

            $cachedResponse = $this->idempotency->check(
                operation: 'order_create',
                idempotencyKey: $idempotencyKey,
                payload: $validated,
                tenantId: (int) $tenantId,
            );

            if (!empty($cachedResponse)) {
                return $this->successResponse($cachedResponse, 'Order retrieved from cache (idempotent)');
            }

            $fraudResult = $this->fraud->check(
                userId: (int) $tenantId,
                operationType: 'order_create',
                amount: $this->calculateTotal($validated['items']),
                ipAddress: $this->request->ip(),
                deviceFingerprint: $this->request->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
            );

            $order = $this->db->transaction(function () use ($validated, $tenantId, $correlationId, $vertical, $isB2B, $fraudResult) {
                $subtotal = $this->calculateSubtotal($validated['items']);
                $shippingCost = $this->calculateShippingCost($validated, $isB2B);
                $discountAmount = $this->calculateDiscount($validated, $isB2B);
                $total = $subtotal + $shippingCost - $discountAmount;
                $platformCommission = $this->commission->calculate($total, $vertical, $isB2B);
                $sellerEarnings = $total - $platformCommission;

                $order = Order::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => $tenantId,
                    'user_id' => $this->guard->id() ?? 0,
                    'business_group_id' => $isB2B ? $this->getBusinessGroupId($validated) : null,
                    'vertical' => $vertical,
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'total' => $total,
                    'platform_commission' => $platformCommission,
                    'seller_earnings' => $sellerEarnings,
                    'currency' => 'RUB',
                    'payment_status' => 'pending',
                    'payment_method' => $validated['payment_method'],
                    'is_b2b' => $isB2B,
                    'inn' => $isB2B ? ($validated['inn'] ?? null) : null,
                    'business_card_id' => $isB2B ? ($validated['business_card_id'] ?? null) : null,
                    'delivery_address' => $validated['delivery_address'] ?? null,
                    'delivery_lat' => $validated['delivery_lat'] ?? null,
                    'delivery_lon' => $validated['delivery_lon'] ?? null,
                    'metadata' => [
                        'fraud_score' => $fraudResult['score'],
                        'fraud_decision' => $fraudResult['decision'],
                    ],
                    'tags' => ['b2b' => $isB2B, 'vertical' => $vertical],
                    'correlation_id' => $correlationId,
                ]);

                foreach ($validated['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_type' => $item['product_type'],
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_type'] . '_' . $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price'],
                        'options' => $item['options'] ?? null,
                        'correlation_id' => $correlationId,
                    ]);
                }

                if ($validated['payment_method'] === 'wallet') {
                    $this->wallet->debit(
                        tenantId: $tenantId,
                        amount: $total,
                        type: 'order_payment',
                        sourceId: $order->id,
                        sourceType: Order::class,
                        correlationId: $correlationId,
                        reason: "Order {$order->uuid} payment",
                    );

                    $order->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }

                if ($isB2B) {
                    $this->bus->dispatch(new ProcessB2BOrderJob(
                        orderId: $order->id,
                        tenantId: $tenantId,
                        correlationId: $correlationId,
                    ));
                }

                $this->notification->send(
                    recipientId: $order->user_id,
                    type: 'order_confirmed',
                    data: [
                        'title' => 'Order Confirmed',
                        'body' => "Your order {$order->uuid} has been confirmed",
                        'order_id' => $order->id,
                        'order_uuid' => $order->uuid,
                        'total' => $order->total,
                    ],
                    correlationId: $correlationId,
                );

                return $order;
            });

            $responseData = [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'status' => $order->status,
                'total' => $order->total,
                'payment_status' => $order->payment_status,
                'is_b2b' => $order->is_b2b,
            ];

            $this->idempotency->record(
                operation: 'order_create',
                idempotencyKey: $idempotencyKey,
                payload: $validated,
                response: $responseData,
                tenantId: (int) $tenantId,
            );

            $this->auditLog('Order created successfully', [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'total' => $order->total,
            ]);

            return $this->successResponse($responseData, 'Order created successfully');

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return $this->errorResponse('Order creation failed: ' . $e->getMessage(), 500);
        }
    }

    public function getOrder(Request $request, string $uuid): JsonResponse
    {
        $correlationId = $this->getCorrelationId();
        $tenantId = $this->guard->id() ?? 0;

        try {
            $order = Order::where('uuid', $uuid)
                ->where('tenant_id', $tenantId)
                ->with('items')
                ->firstOrFail();

            $this->auditLog('Order retrieved', [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
            ]);

            return $this->successResponse($order->toArray());

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Order retrieval failed', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'correlation_id' => $correlationId,
            ]);

            return $this->errorResponse('Order not found', 404);
        }
    }

    public function listOrders(Request $request): JsonResponse
    {
        $correlationId = $this->getCorrelationId();
        $tenantId = $this->guard->id() ?? 0;
        $vertical = $request->input('vertical');
        $status = $request->input('status');
        $isB2B = $request->boolean('is_b2b');

        try {
            $query = Order::where('tenant_id', $tenantId)
                ->with('items');

            if ($vertical) {
                $query->where('vertical', $vertical);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($isB2B !== null) {
                $query->where('is_b2b', $isB2B);
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 20));

            $this->auditLog('Orders listed', [
                'count' => $orders->total(),
            ]);

            return $this->successResponse($orders->toArray());

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Order listing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return $this->errorResponse('Failed to list orders', 500);
        }
    }

    private function calculateSubtotal(array $items): int
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }
        return $subtotal;
    }

    private function calculateTotal(array $items): int
    {
        return $this->calculateSubtotal($items);
    }

    private function calculateShippingCost(array $validated, bool $isB2B): int
    {
        if ($isB2B) {
            return 0;
        }

        return isset($validated['delivery_address']) ? 50000 : 0;
    }

    private function calculateDiscount(array $validated, bool $isB2B): int
    {
        if (!$isB2B) {
            return 0;
        }

        $subtotal = $this->calculateSubtotal($validated['items']);

        if ($subtotal >= 10000000) {
            return (int) ($subtotal * 0.10);
        }

        if ($subtotal >= 5000000) {
            return (int) ($subtotal * 0.05);
        }

        return 0;
    }

    private function getBusinessGroupId(array $validated): ?int
    {
        if (!isset($validated['business_card_id'])) {
            return null;
        }

        return $this->db->table('business_groups')
            ->where('business_card_id', $validated['business_card_id'])
            ->value('id');
    }
}
