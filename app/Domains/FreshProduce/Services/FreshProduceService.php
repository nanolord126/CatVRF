<?php declare(strict_types=1);

namespace App\Domains\FreshProduce\Services;

use App\Domains\FreshProduce\Events\BoxDelivered;
use App\Domains\FreshProduce\Events\ProduceOrderCreated;
use App\Domains\FreshProduce\Events\QualityIssueDetected;
use App\Domains\FreshProduce\Models\ProduceBox;
use App\Domains\FreshProduce\Models\ProduceOrder;
use App\Domains\FreshProduce\Models\ProduceSubscription;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Сервис управления заказами и подписками свежих продуктов — КАНОН 2026.
 * Единственная точка мутаций для FreshProduce-вертикали.
 */
final class FreshProduceService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly InventoryManagementService $inventory,
    ) {}

    // -------------------------------------------------------------------------
    // Оформление заказа
    // -------------------------------------------------------------------------

    public function placeOrder(
        int $clientId,
        int $boxId,
        string $deliveryAddress,
        string $deliveryDate,
        string $deliverySlot,
        ?int $subscriptionId = null,
        string $correlationId = '',
    ): ProduceOrder {


        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        // Rate limiting
        $rlKey = "fresh_produce:order:{$clientId}";
        if (RateLimiter::tooManyAttempts($rlKey, 10)) {
            throw new \RuntimeException('Слишком много заказов. Попробуйте позже.');
        }
        RateLimiter::hit($rlKey, 3600);

        // Fraud check
        $fraud = $this->fraudControl->check(
            userId: $clientId,
            operationType: 'produce_order',
            amount: 0,
            correlationId: $correlationId,
        );
        if ($fraud['decision'] === 'block') {
            Log::channel('audit')->warning('FreshProduce: fraud block on order', [
                'client_id'      => $clientId,
                'score'          => $fraud['score'],
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Оформление заказа временно недоступно.');
        }

        $box = ProduceBox::findOrFail($boxId);

        return DB::transaction(function () use (
            $clientId, $box, $deliveryAddress, $deliveryDate, $deliverySlot, $subscriptionId, $correlationId
        ): ProduceOrder {
            $order = ProduceOrder::create([
                'tenant_id'               => $box->tenant_id,
                'client_id'               => $clientId,
                'subscription_id'         => $subscriptionId,
                'uuid'                    => (string) Str::uuid()->toString(),
                'correlation_id'          => $correlationId,
                'idempotency_key'         => md5("{$clientId}:{$box->id}:{$deliveryDate}:{$deliverySlot}"),
                'items'                   => $box->contents ?? [],
                'total_amount'            => $box->price,
                'delivery_address'        => $deliveryAddress,
                'delivery_date'           => $deliveryDate,
                'delivery_slot'           => $deliverySlot,
                'status'                  => 'pending',
                'payment_status'          => 'awaiting',
                'tags'                    => ['source:fresh_produce'],
            ]);

            Log::channel('audit')->info('FreshProduce: order placed', [
                'order_id'       => $order->id,
                'client_id'      => $clientId,
                'box_id'         => $box->id,
                'delivery_date'  => $deliveryDate,
                'correlation_id' => $correlationId,
            ]);

            event(new ProduceOrderCreated($order, $correlationId));

            return $order;
        });
    }

    // -------------------------------------------------------------------------
    // Подписка
    // -------------------------------------------------------------------------

    public function subscribe(
        int $clientId,
        int $boxId,
        string $frequency,
        string $deliveryAddress,
        string $preferredSlot,
        string $correlationId = '',
    ): ProduceSubscription {


        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        $box = ProduceBox::findOrFail($boxId);

        return DB::transaction(function () use (
            $clientId, $box, $frequency, $deliveryAddress, $preferredSlot, $correlationId
        ): ProduceSubscription {
            $nextDate = match ($frequency) {
                'weekly'    => now()->addWeek()->format('Y-m-d'),
                'biweekly'  => now()->addWeeks(2)->format('Y-m-d'),
                'monthly'   => now()->addMonth()->format('Y-m-d'),
                default     => now()->addWeek()->format('Y-m-d'),
            };

            $sub = ProduceSubscription::create([
                'tenant_id'          => $box->tenant_id,
                'client_id'          => $clientId,
                'box_id'             => $boxId,
                'uuid'               => (string) Str::uuid()->toString(),
                'correlation_id'     => $correlationId,
                'frequency'          => $frequency,
                'delivery_address'   => $deliveryAddress,
                'preferred_slot'     => $preferredSlot,
                'next_delivery_date' => $nextDate,
                'total_deliveries'   => 0,
                'price_per_box'      => $box->price,
                'status'             => 'active',
                'tags'               => ['source:fresh_produce', 'type:subscription'],
            ]);

            Log::channel('audit')->info('FreshProduce: subscription created', [
                'subscription_id' => $sub->id,
                'client_id'       => $clientId,
                'box_id'          => $boxId,
                'frequency'       => $frequency,
                'correlation_id'  => $correlationId,
            ]);

            return $sub;
        });
    }

    // -------------------------------------------------------------------------
    // Контроль качества
    // -------------------------------------------------------------------------

    public function reportQualityIssue(
        int $orderId,
        string $description,
        string $photoUrl,
        int $reportedBy,
        string $correlationId = '',
    ): bool {


        $correlationId = $correlationId ?: (string) Str::uuid()->toString();
        $order = ProduceOrder::findOrFail($orderId);

        return DB::transaction(function () use ($order, $description, $photoUrl, $reportedBy, $correlationId): bool {
            $order->update([
                'quality_photo_url'  => $photoUrl,
                'quality_checked_at' => now(),
                'meta'               => array_merge((array) $order->meta, ['quality_issue' => $description]),
            ]);

            Log::channel('audit')->warning('FreshProduce: quality issue reported', [
                'order_id'       => $order->id,
                'description'    => $description,
                'reported_by'    => $reportedBy,
                'correlation_id' => $correlationId,
            ]);

            event(new QualityIssueDetected($order, $description, $correlationId));

            return true;
        });
    }

    // -------------------------------------------------------------------------
    // Доставка
    // -------------------------------------------------------------------------

    public function markDelivered(
        int $orderId,
        int $courierId,
        string $correlationId = '',
    ): ProduceOrder {


        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        return DB::transaction(function () use ($orderId, $courierId, $correlationId): ProduceOrder {
            $order = ProduceOrder::lockForUpdate()->findOrFail($orderId);

            $order->update([
                'status'       => 'delivered',
                'delivered_at' => now(),
                'courier_id'   => $courierId,
            ]);

            // Обновляем счётчик подписки
            if ($order->subscription_id) {
                ProduceSubscription::where('id', $order->subscription_id)
                    ->increment('total_deliveries');
            }

            Log::channel('audit')->info('FreshProduce: order delivered', [
                'order_id'       => $order->id,
                'courier_id'     => $courierId,
                'correlation_id' => $correlationId,
            ]);

            event(new BoxDelivered($order, $correlationId));

            return $order;
        });
    }

    // -------------------------------------------------------------------------
    // Доступные боксы
    // -------------------------------------------------------------------------

    public function getAvailableBoxes(int $tenantId): Collection
    {


        return ProduceBox::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('price')
            ->get();
    }
}
