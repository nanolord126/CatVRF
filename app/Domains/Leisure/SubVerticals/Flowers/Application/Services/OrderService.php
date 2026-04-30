<?php

declare(strict_types=1);

namespace Modules\Flowers\Application\Services;

use Modules\Flowers\Domain\Entities\Order;
use Modules\Flowers\Domain\Entities\OrderItem;
use Modules\Flowers\Domain\Entities\OrderModifier;
use Modules\Flowers\Domain\Entities\Client;
use Modules\Flowers\Domain\Entities\Product;
use Modules\Flowers\Domain\Entities\Flower;
use Modules\Flowers\Domain\Entities\DeliverySlot;
use Modules\Flowers\Domain\Repositories\OrderRepositoryInterface;
use Modules\Flowers\Domain\Repositories\ClientRepositoryInterface;
use Modules\Flowers\Domain\Repositories\ProductRepositoryInterface;
use Modules\Flowers\Domain\Repositories\FlowerRepositoryInterface;
use Modules\Flowers\Domain\Repositories\DeliverySlotRepositoryInterface;
use Modules\Flowers\Domain\Repositories\FloristRepositoryInterface;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Modules\Flowers\Domain\Enums\FreshnessStatus;
use Modules\Flowers\Domain\Events\OrderCreated;
use Modules\Flowers\Domain\Events\OrderStatusChanged;
use Modules\Flowers\Domain\Events\FlowersReserved;
use Modules\Flowers\Domain\Events\FlowersConsumed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final class OrderService
{
    use WithAuditLogging;

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ClientRepositoryInterface $clientRepository,
        private ProductRepositoryInterface $productRepository,
        private FlowerRepositoryInterface $flowerRepository,
        private DeliverySlotRepositoryInterface $deliverySlotRepository,
        private FloristRepositoryInterface $floristRepository,
        private readonly AuditService $auditService,
    ) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Find or create client
            $client = $this->findOrCreateClient($data);

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Calculate total amount
            $subtotal = $this->calculateSubtotal($data['items'], $data['modifiers'] ?? []);
            $deliveryFee = $data['delivery_fee'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $deliveryFee - $discountAmount;

            // Book delivery slot if provided
            if (isset($data['delivery_slot_id'])) {
                $this->deliverySlotRepository->bookSlot($data['delivery_slot_id']);
            }

            // Create order
            $order = Order::create(
                venueId: $data['venue_id'],
                clientId: $client->id,
                tenantId: $data['tenant_id'],
                orderNumber: $orderNumber,
                recipientName: $data['recipient_name'],
                recipientPhone: $data['recipient_phone'],
                totalAmount: $totalAmount,
                deliveryType: $data['delivery_type'] ?? 'delivery',
                deliveryAddress: $data['delivery_address'] ?? null,
                deliverySlotId: $data['delivery_slot_id'] ?? null,
                deliveryDate: isset($data['delivery_date']) ? CarbonImmutable::parse($data['delivery_date']) : null,
                source: $data['source'] ?? 'website',
            );

            // Add additional fields
            $order = new Order(
                ...$order->toArray(),
                deliveryInstructions: $data['delivery_instructions'] ?? null,
                subtotal: $subtotal,
                deliveryFee: $deliveryFee,
                discountAmount: $discountAmount,
                cardMessage: $data['card_message'] ?? null,
                notes: $data['notes'] ?? null,
                isUrgent: $data['is_urgent'] ?? false,
                isCorporate: $data['is_corporate'] ?? false,
            );

            $order = $this->orderRepository->save($order);

            // Create order items
            foreach ($data['items'] as $item) {
                $this->createOrderItem($order->id, $item);
            }

            // Create order modifiers
            if (isset($data['modifiers'])) {
                foreach ($data['modifiers'] as $modifier) {
                    $this->createOrderModifier($order->id, $modifier);
                }
            }

            // Reserve flowers for the order
            $this->reserveFlowersForOrder($order);

            // Dispatch event
            Event::dispatch(new OrderCreated($order));

            return $order;
        });
    }

    public function updateOrderStatus(int $orderId, OrderStatus $newStatus, ?string $reason = null): Order
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new \RuntimeException('Order not found');
        }

        if (!$order->canTransitionTo($newStatus)) {
            throw new \RuntimeException("Cannot transition from {$order->status->value} to {$newStatus->value}");
        }

        $previousStatus = $order->status;
        $order = $order->transitionTo($newStatus, $reason);
        $order = $this->orderRepository->save($order);

        // Handle status-specific logic
        $this->handleStatusChange($order, $previousStatus);

        Event::dispatch(new OrderStatusChanged($order, $previousStatus));

        return $order;
    }

    public function assignFlorist(int $orderId, int $floristId): Order
    {
        $order = $this->orderRepository->findById($orderId);
        $florist = $this->floristRepository->findById($floristId);

        if (!$order) {
            throw new \RuntimeException('Order not found');
        }

        if (!$florist) {
            throw new \RuntimeException('Florist not found');
        }

        if (!$florist->isAvailable) {
            throw new \RuntimeException('Florist is not available');
        }

        $order = $order->assignFlorist($floristId);
        $order = $this->orderRepository->save($order);

        return $order;
    }

    public function cancelOrder(int $orderId, string $reason): Order
    {
        return $this->updateOrderStatus($orderId, OrderStatus::CANCELLED, $reason);
    }

    public function markAsDelivered(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->updateOrderStatus($orderId, OrderStatus::DELIVERED);

            // Consume flowers
            $this->consumeFlowersForOrder($order);

            // Update client statistics
            $client = $this->clientRepository->findById($order->clientId);
            if ($client) {
                $client = $client->addOrder($order->totalAmount);
                $this->clientRepository->save($client);
            }

            // Update florist statistics
            if ($order->floristId) {
                $florist = $this->floristRepository->findById($order->floristId);
                if ($florist) {
                    $florist = $florist->completeOrder();
                    $this->floristRepository->save($florist);
                }
            }

            return $order;
        });
    }

    private function findOrCreateClient(array $data): Client
    {
        $client = $this->clientRepository->findByPhone($data['client_phone']);

        if (!$client) {
            $client = Client::create(
                tenantId: $data['tenant_id'],
                firstName: $data['client_first_name'],
                lastName: $data['client_last_name'],
                phone: $data['client_phone'],
                email: $data['client_email'] ?? null,
            );
            $client = $this->clientRepository->save($client);
        }

        return $client;
    }

    private function generateOrderNumber(): string
    {
        return 'FL-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function calculateSubtotal(array $items, array $modifiers): float
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $product = $this->productRepository->findById($item['product_id']);
            if ($product) {
                $subtotal += $product->getCurrentPrice() * $item['quantity'];
            }
        }

        foreach ($modifiers as $modifier) {
            $subtotal += $modifier['price'] * $modifier['quantity'];
        }

        return $subtotal;
    }

    private function createOrderItem(int $orderId, array $itemData): void
    {
        $product = $this->productRepository->findById($itemData['product_id']);
        
        if (!$product) {
            throw new \RuntimeException('Product not found');
        }

        $orderItem = OrderItem::create(
            orderId: $orderId,
            productId: $itemData['product_id'],
            productName: $product->name,
            quantity: $itemData['quantity'],
            unitPrice: $product->getCurrentPrice(),
            customizations: $itemData['customizations'] ?? null,
        );

        // Save order item (would need OrderItemRepository in real implementation)
        // For now, we'll use the model directly
        $model = \Modules\Flowers\Infrastructure\Models\OrderItemModel::fromDomain($orderItem);
        $model->save();
    }

    private function createOrderModifier(int $orderId, array $modifierData): void
    {
        $orderModifier = OrderModifier::create(
            orderId: $orderId,
            modifierId: $modifierData['modifier_id'],
            modifierName: $modifierData['name'],
            quantity: $modifierData['quantity'],
            unitPrice: $modifierData['price'],
        );

        $model = \Modules\Flowers\Infrastructure\Models\OrderModifierModel::fromDomain($orderModifier);
        $model->save();
    }

    private function reserveFlowersForOrder(Order $order): void
    {
        $orderItems = \Modules\Flowers\Infrastructure\Models\OrderItemModel::where('order_id', $order->id)->get();

        foreach ($orderItems as $item) {
            $product = $this->productRepository->findById($item->product_id);
            if (!$product) {
                continue;
            }

            $productModel = \Modules\Flowers\Infrastructure\Models\ProductModel::find($product->id);
            if (!$productModel) {
                continue;
            }

            $productFlowers = $productModel->flowers;
            
            foreach ($productFlowers as $productFlower) {
                $flower = $this->flowerRepository->findById($productFlower->id);
                if ($flower && $flower->canReserve($productFlower->pivot->quantity * $item->quantity)) {
                    $flower = $flower->reserve($productFlower->pivot->quantity * $item->quantity);
                    $this->flowerRepository->save($flower);
                }
            }
        }

        Event::dispatch(new FlowersReserved($order));
    }

    private function consumeFlowersForOrder(Order $order): void
    {
        $orderItems = \Modules\Flowers\Infrastructure\Models\OrderItemModel::where('order_id', $order->id)->get();

        foreach ($orderItems as $item) {
            $product = $this->productRepository->findById($item->product_id);
            if (!$product) {
                continue;
            }

            $productModel = \Modules\Flowers\Infrastructure\Models\ProductModel::find($product->id);
            if (!$productModel) {
                continue;
            }

            $productFlowers = $productModel->flowers;
            
            foreach ($productFlowers as $productFlower) {
                $flower = $this->flowerRepository->findById($productFlower->id);
                if ($flower) {
                    $flower = $flower->consume($productFlower->pivot->quantity * $item->quantity);
                    $this->flowerRepository->save($flower);
                }
            }
        }

        Event::dispatch(new FlowersConsumed($order));
    }

    private function handleStatusChange(Order $order, OrderStatus $previousStatus): void
    {
        match ($order->status) {
            OrderStatus::CONFIRMED => $this->handleConfirmed($order),
            OrderStatus::IN_ASSEMBLY => $this->handleAssemblyStarted($order),
            OrderStatus::ASSEMBLED => $this->handleAssembled($order),
            OrderStatus::CANCELLED => $this->handleCancelled($order, $previousStatus),
            default => null,
        };
    }

    private function handleConfirmed(Order $order): void
    {
        // Send confirmation notification
        // Could dispatch a job to send email/SMS
    }

    private function handleAssemblyStarted(Order $order): void
    {
        // Notify florist
    }

    private function handleAssembled(Order $order): void
    {
        // Notify quality check team
    }

    private function handleCancelled(Order $order, OrderStatus $previousStatus): void
    {
        // Release flower reservations
        $orderItems = \Modules\Flowers\Infrastructure\Models\OrderItemModel::where('order_id', $order->id)->get();

        foreach ($orderItems as $item) {
            $product = $this->productRepository->findById($item->product_id);
            if (!$product) {
                continue;
            }

            $productModel = \Modules\Flowers\Infrastructure\Models\ProductModel::find($product->id);
            if (!$productModel) {
                continue;
            }

            $productFlowers = $productModel->flowers;
            
            foreach ($productFlowers as $productFlower) {
                $flower = $this->flowerRepository->findById($productFlower->id);
                if ($flower) {
                    $flower = $flower->releaseReservation($productFlower->pivot->quantity * $item->quantity);
                    $this->flowerRepository->save($flower);
                }
            }
        }

        // Release delivery slot
        if ($order->deliverySlotId) {
            $this->deliverySlotRepository->releaseBooking($order->deliverySlotId);
        }
    }
}
