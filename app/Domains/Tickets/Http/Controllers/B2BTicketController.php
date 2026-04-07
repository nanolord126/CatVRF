<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class B2BTicketController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}
        public function storefronts(): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => B2BTicketStorefront::where('is_active', true)
                    ->where('is_verified', true)
                    ->paginate(20),
                'correlation_id' => Str::uuid(),
            ]);
        }

        public function createStorefront(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('B2BTicket storefront create blocked', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id, 'score' => $fraudResult['score']]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            $this->logger->info('B2BTicket storefront create start', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id]);

            try {
                $this->authorize('createStorefront', B2BTicketStorefront::class);

                $validated = $request->validate([
                    'company_name'       => 'required|string|max:255',
                    'inn'                => 'required|string|unique:b2b_ticket_storefronts,inn',
                    'description'        => 'nullable|string',
                    'service_categories' => 'nullable|json',
                    'wholesale_discount' => 'nullable|numeric|between:0,100',
                    'min_order_amount'   => 'integer|min:1000',
                ]);

                $this->db->transaction(function () use ($validated, $correlationId) {
                    B2BTicketStorefront::create([
                        'uuid'           => Str::uuid(),
                        'tenant_id'      => $request->user()->tenant_id,
                        ...$validated,
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('B2BTicket storefront created', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Витрина создана', 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('B2BTicket storefront create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка при создании витрины', 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function createOrder(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('B2BTicket order create blocked', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id, 'score' => $fraudResult['score']]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $validated = $request->validate([
                    'b2b_ticket_storefront_id' => 'required|exists:b2b_ticket_storefronts,id',
                    'company_contact_person'   => 'required|string|max:255',
                    'company_phone'            => 'required|string|max:20',
                    'items_json'               => 'required|json',
                    'total_amount'             => 'required|numeric|min:1',
                ]);

                $this->db->transaction(function () use ($validated, $correlationId) {
                    B2BTicketOrder::create([
                        'uuid'              => Str::uuid(),
                        'tenant_id'         => $request->user()->tenant_id,
                        'order_number'      => 'B2B-' . Str::random(8),
                        'commission_amount' => (int) ($validated['total_amount'] * 0.14),
                        'status'            => 'pending',
                        ...$validated,
                        'correlation_id'    => $correlationId,
                    ]);
                });

                $this->logger->info('B2BTicket order created', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Заказ создан', 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('B2BTicket order create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка при создании заказа', 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function myB2BOrders(): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => B2BTicketOrder::where('tenant_id', $request->user()->tenant_id)
                    ->latest()
                    ->paginate(20),
                'correlation_id' => Str::uuid(),
            ]);
        }

        public function approveOrder(int $id): JsonResponse
        {
            try {
                $order = B2BTicketOrder::findOrFail($id);
                $this->authorize('approveOrder', $order);

                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($order, $correlationId) {
                    $order->update(['status' => 'approved']);
                });

                $this->logger->info('B2BTicket order approved', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id, 'order_id' => $id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Заказ одобрен', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка при одобрении заказа', 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function rejectOrder(int $id, Request $request): JsonResponse
        {
            try {
                $order = B2BTicketOrder::findOrFail($id);
                $this->authorize('rejectOrder', $order);

                $correlationId = Str::uuid()->toString();
                $reason        = $request->get('reason', '');

                $this->db->transaction(function () use ($order, $reason) {
                    $order->update(['status' => 'rejected', 'notes' => $reason]);
                });

                $this->logger->info('B2BTicket order rejected', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id, 'order_id' => $id, 'reason' => $reason]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Заказ отклонен', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка при отклонении заказа', 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function verifyInn(int $id): JsonResponse
        {
            try {
                $this->authorize('verifyInn', B2BTicketStorefront::class);

                $storefront    = B2BTicketStorefront::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($storefront) {
                    $storefront->update(['is_verified' => true]);
                });

                $this->logger->info('B2BTicket storefront verified', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id, 'storefront_id' => $id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Витрина верифицирована', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка при верификации', 'correlation_id' => Str::uuid()], 500);
            }
        }
}
