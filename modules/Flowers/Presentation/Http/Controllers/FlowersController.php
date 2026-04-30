<?php

declare(strict_types=1);

namespace Modules\Flowers\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Flowers\Application\Services\OrderService;
use Modules\Flowers\Application\Services\FloristAssignmentService;
use Modules\Flowers\Application\Services\FreshnessService;
use Modules\Flowers\Domain\Repositories\OrderRepositoryInterface;
use Modules\Flowers\Domain\Repositories\ProductRepositoryInterface;
use Modules\Flowers\Domain\Repositories\FlowerRepositoryInterface;
use Modules\Flowers\Domain\Repositories\FloristRepositoryInterface;
use Modules\Flowers\Domain\Repositories\DeliverySlotRepositoryInterface;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

final readonly class FlowersController
{
    public function __construct(
        private OrderService $orderService,
        private FloristAssignmentService $floristAssignmentService,
        private FreshnessService $freshnessService,
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository,
        private FlowerRepositoryInterface $flowerRepository,
        private FloristRepositoryInterface $floristRepository,
        private DeliverySlotRepositoryInterface $deliverySlotRepository,
        private LoggerInterface $logger,
    ) {}

    public function createOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'venue_id' => 'required|integer',
                'tenant_id' => 'required|integer',
                'client_first_name' => 'required|string|max:100',
                'client_last_name' => 'required|string|max:100',
                'client_phone' => 'required|string|max:20',
                'client_email' => 'nullable|email|max:255',
                'recipient_name' => 'required|string|max:200',
                'recipient_phone' => 'required|string|max:20',
                'delivery_type' => 'required|string|in:delivery,pickup,courier',
                'delivery_address' => 'nullable|string|max:500',
                'delivery_date' => 'nullable|date|after:today',
                'delivery_slot_id' => 'nullable|integer',
                'delivery_fee' => 'nullable|numeric|min:0',
                'venue_address' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.customizations' => 'nullable|array',
                'modifiers' => 'nullable|array',
                'modifiers.*.modifier_id' => 'required|integer',
                'modifiers.*.name' => 'required|string|max:255',
                'modifiers.*.quantity' => 'required|integer|min:1',
                'modifiers.*.price' => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'source' => 'nullable|string|max:50',
            ]);

            $order = $this->orderService->createOrder($request->all());

            return new JsonResponse([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->orderNumber,
                    'client_id' => $order->clientId,
                    'total_amount' => $order->totalAmount,
                    'status' => $order->status->value,
                    'delivery_type' => $order->deliveryType,
                    'delivery_address' => $order->deliveryAddress,
                    'delivery_date' => $order->deliveryDate?->format('Y-m-d'),
                    'created_at' => $order->createdAt->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Flower order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function updateOrderStatus(Request $request, int $orderId): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|string|in:pending,confirmed,in_assembly,assembled,ready_for_delivery,out_for_delivery,delivered,cancelled',
                'reason' => 'nullable|string|max:1000',
            ]);

            $order = $this->orderService->updateOrderStatus(
                $orderId,
                OrderStatus::from($request->input('status')),
                $request->input('reason')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->orderNumber,
                    'status' => $order->status->value,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Order status update failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function assignFlorist(Request $request, int $orderId): JsonResponse
    {
        try {
            $request->validate([
                'florist_id' => 'required|integer',
            ]);

            $order = $this->orderService->assignFlorist(
                $orderId,
                (int) $request->input('florist_id')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Florist assigned successfully',
                'order' => [
                    'id' => $order->id,
                    'florist_id' => $order->floristId,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Florist assignment failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function cancelOrder(Request $request, int $orderId): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:1000',
            ]);

            $order = $this->orderService->cancelOrder(
                $orderId,
                $request->input('reason')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status->value,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Order cancellation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function markAsDelivered(Request $request, int $orderId): JsonResponse
    {
        try {
            $order = $this->orderService->markAsDelivered($orderId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Order marked as delivered successfully',
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status->value,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Order delivery marking failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function getOrder(Request $request, int $orderId): JsonResponse
    {
        try {
            $order = $this->orderRepository->findById($orderId);
            if (!$order) {
                return new JsonResponse(['error' => 'Order not found'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->orderNumber,
                    'client_id' => $order->clientId,
                    'venue_id' => $order->venueId,
                    'total_amount' => $order->totalAmount,
                    'status' => $order->status->value,
                    'delivery_type' => $order->deliveryType,
                    'delivery_address' => $order->deliveryAddress,
                    'delivery_date' => $order->deliveryDate?->format('Y-m-d'),
                    'delivery_slot_id' => $order->deliverySlotId,
                    'florist_id' => $order->floristId,
                    'recipient_name' => $order->recipientName,
                    'recipient_phone' => $order->recipientPhone,
                    'created_at' => $order->createdAt->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Order retrieval failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getAvailableFlorists(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'venue_id' => 'required|integer',
            ]);

            $florists = $this->floristRepository->findAvailableByVenue(
                (int) $request->input('venue_id')
            );

            return new JsonResponse([
                'success' => true,
                'florists' => array_map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'is_available' => $f->isAvailable,
                    'completed_orders' => $f->completedOrders,
                ], $florists),
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Available florists retrieval failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getDeliverySlots(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'venue_id' => 'required|integer',
                'date' => 'required|date|after_or_equal:today',
            ]);

            $date = CarbonImmutable::parse($request->input('date'));
            $slots = $this->deliverySlotRepository->findAvailableByVenueAndDate(
                (int) $request->input('venue_id'),
                $date
            );

            return new JsonResponse([
                'success' => true,
                'slots' => array_map(fn ($s) => [
                    'id' => $s->id,
                    'start_time' => $s->startTime->format('H:i'),
                    'end_time' => $s->endTime->format('H:i'),
                    'is_available' => $s->isAvailable,
                ], $slots),
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Delivery slots retrieval failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }
}
