<?php declare(strict_types=1);

namespace App\Domains\Travel\TravelTourism\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class B2BTravelController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Список активных B2B витрин для путешествий.
     */
    public function storefronts(): JsonResponse
    {
        $data = \App\Domains\Travel\TravelTourism\Models\B2BTravelStorefront::query()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->paginate(20);

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }

    /**
     * Создание B2B витрины.
     */
    public function createStorefront(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $this->authorize('createStorefront', \App\Domains\Travel\TravelTourism\Models\B2BTravelStorefront::class);

            $validated = $request->validate([
                'company_name' => 'required',
                'inn' => 'required|unique:b2b_travel_storefronts,inn',
                'description' => 'nullable',
                'service_categories' => 'nullable|json',
                'wholesale_discount' => 'nullable|numeric|between:0,100',
                'min_order_amount' => 'integer|min:1000',
            ]);

            $this->db->transaction(function () use ($validated, $request, $correlationId): void {
                \App\Domains\Travel\TravelTourism\Models\B2BTravelStorefront::create(
                    array_merge(
                        [
                            'uuid' => Str::uuid()->toString(),
                            'tenant_id' => $request->user()->tenant_id,
                            'correlation_id' => $correlationId,
                        ],
                        $validated,
                    ),
                );
            });

            $this->logger->info('B2B Travel storefront created', [
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Витрина создана',
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $this->logger->error('B2B Travel storefront creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Ошибка',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Создание B2B заказа.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $validated = $request->validate([
                'b2b_travel_storefront_id' => 'required|exists:b2b_travel_storefronts,id',
                'company_contact_person' => 'required',
                'company_phone' => 'required',
                'items_json' => 'required|json',
                'total_amount' => 'required|numeric|min:1',
            ]);

            $this->db->transaction(function () use ($validated, $request, $correlationId): void {
                \App\Domains\Travel\TravelTourism\Models\B2BTravelOrder::create(
                    array_merge(
                        [
                            'uuid' => Str::uuid()->toString(),
                            'tenant_id' => $request->user()->tenant_id,
                            'order_number' => 'B2B-' . Str::random(8),
                            'commission_amount' => (int) ($validated['total_amount'] * 0.14),
                            'status' => 'pending',
                            'correlation_id' => $correlationId,
                        ],
                        $validated,
                    ),
                );
            });

            $this->logger->info('B2B Travel order created', [
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Заказ создан',
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $this->logger->error('B2B Travel order creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Ошибка',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Список B2B заказов текущего пользователя.
     */
    public function myB2BOrders(Request $request): JsonResponse
    {
        $data = \App\Domains\Travel\TravelTourism\Models\B2BTravelOrder::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }

    /**
     * Одобрение B2B заказа.
     */
    public function approveOrder(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $order = \App\Domains\Travel\TravelTourism\Models\B2BTravelOrder::findOrFail($id);
            $this->authorize('approveOrder', $order);

            $this->db->transaction(function () use ($order): void {
                $order->update(['status' => 'approved']);
            });

            $this->logger->info('B2B Travel order approved', [
                'order_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Одобрено',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ошибка',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Отклонение B2B заказа.
     */
    public function rejectOrder(int $id, Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $order = \App\Domains\Travel\TravelTourism\Models\B2BTravelOrder::findOrFail($id);
            $this->authorize('rejectOrder', $order);

            $this->db->transaction(function () use ($order, $request): void {
                $order->update([
                    'status' => 'rejected',
                    'notes' => $request->get('reason', ''),
                ]);
            });

            $this->logger->info('B2B Travel order rejected', [
                'order_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Отклонено',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ошибка',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Верификация ИНН витрины.
     */
    public function verifyInn(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $this->authorize('verifyInn', \App\Domains\Travel\TravelTourism\Models\B2BTravelStorefront::class);

            $this->db->transaction(function () use ($id): void {
                \App\Domains\Travel\TravelTourism\Models\B2BTravelStorefront::findOrFail($id)
                    ->update(['is_verified' => true]);
            });

            $this->logger->info('B2B Travel INN verified', [
                'storefront_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Верифицировано',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ошибка',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
