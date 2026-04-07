<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Http\Controllers;

use App\Domains\Fashion\FashionRetail\Models\B2BFashionOrder;
use App\Domains\Fashion\FashionRetail\Models\B2BFashionStorefront;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class B2BFashionController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Список B2B-витрин текущего бизнес-аккаунта.
     */
    public function storefronts(Request $request): JsonResponse
    {
        $businessGroupId = (int) $request->get('business_group_id');

        $storefronts = B2BFashionStorefront::where('business_group_id', $businessGroupId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return new JsonResponse([
            'success' => true,
            'data' => $storefronts,
            'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
        ]);
    }

    /**
     * Создать новую B2B-витрину.
     */
    public function createStorefront(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $userId = (int) $request->user()?->id;

        $this->fraud->check(
            userId: $userId,
            operationType: 'b2b_storefront_create',
            amount: 0,
            correlationId: $correlationId,
        );

        $storefront = $this->db->transaction(function () use ($request, $correlationId): B2BFashionStorefront {
            $storefront = B2BFashionStorefront::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'business_group_id' => (int) $request->get('business_group_id'),
                'name' => $request->get('name'),
                'description' => $request->get('description', ''),
                'correlation_id' => $correlationId,
                'status' => 'active',
                'tags' => ['b2b_fashion' => true],
            ]);

            $this->audit->log(
                action: 'b2b_storefront_created',
                subjectType: B2BFashionStorefront::class,
                subjectId: $storefront->id,
                old: [],
                new: $storefront->toArray(),
                correlationId: $correlationId,
            );

            return $storefront;
        });

        return new JsonResponse([
            'success' => true,
            'data' => $storefront,
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * Создать B2B-заказ.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $userId = (int) $request->user()?->id;

        $this->fraud->check(
            userId: $userId,
            operationType: 'b2b_fashion_order',
            amount: (int) $request->get('total_kopecks', 0),
            correlationId: $correlationId,
        );

        $order = $this->db->transaction(function () use ($request, $correlationId): B2BFashionOrder {
            $order = B2BFashionOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'business_group_id' => (int) $request->get('business_group_id'),
                'storefront_id' => (int) $request->get('storefront_id'),
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'total_kopecks' => (int) $request->get('total_kopecks'),
                'items_json' => $request->get('items', []),
                'payment_status' => 'pending',
                'tags' => ['b2b_order' => true],
            ]);

            $this->audit->log(
                action: 'b2b_fashion_order_created',
                subjectType: B2BFashionOrder::class,
                subjectId: $order->id,
                old: [],
                new: $order->toArray(),
                correlationId: $correlationId,
            );

            return $order;
        });

        return new JsonResponse([
            'success' => true,
            'data' => $order,
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * Список B2B-заказов текущего бизнеса.
     */
    public function myB2BOrders(Request $request): JsonResponse
    {
        $businessGroupId = (int) $request->get('business_group_id');

        $orders = B2BFashionOrder::where('business_group_id', $businessGroupId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return new JsonResponse([
            'success' => true,
            'data' => $orders,
            'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
        ]);
    }

    /**
     * Утвердить B2B-заказ.
     */
    public function approveOrder(Request $request, int $orderId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $order = B2BFashionOrder::findOrFail($orderId);
        $previousStatus = $order->status;

        $order->update([
            'status' => 'approved',
            'correlation_id' => $correlationId,
        ]);

        $this->audit->log(
            action: 'b2b_fashion_order_approved',
            subjectType: B2BFashionOrder::class,
            subjectId: $orderId,
            old: ['status' => $previousStatus],
            new: ['status' => 'approved'],
            correlationId: $correlationId,
        );

        return new JsonResponse([
            'success' => true,
            'data' => $order->fresh(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Отклонить B2B-заказ.
     */
    public function rejectOrder(Request $request, int $orderId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $order = B2BFashionOrder::findOrFail($orderId);
        $previousStatus = $order->status;

        $order->update([
            'status' => 'rejected',
            'correlation_id' => $correlationId,
        ]);

        $this->audit->log(
            action: 'b2b_fashion_order_rejected',
            subjectType: B2BFashionOrder::class,
            subjectId: $orderId,
            old: ['status' => $previousStatus],
            new: ['status' => 'rejected'],
            correlationId: $correlationId,
        );

        return new JsonResponse([
            'success' => true,
            'data' => $order->fresh(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Верификация ИНН для B2B-доступа.
     */
    public function verifyInn(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $inn = $request->get('inn', '');

        if (strlen($inn) !== 10 && strlen($inn) !== 12) {
            return new JsonResponse([
                'success' => false,
                'error' => 'ИНН должен содержать 10 или 12 цифр',
                'correlation_id' => $correlationId,
            ], 422);
        }

        $this->logger->info('B2B INN verification requested', [
            'inn' => $inn,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'success' => true,
            'inn' => $inn,
            'verified' => true,
            'correlation_id' => $correlationId,
        ]);
    }
}
