<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProcessOrderDeliveryJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public $tries = 5;
        public $maxExceptions = 2;

        public function __construct(
            public GroceryOrder $order,
            public string $correlationId,
        ) {
            $this->onQueue('grocery-delivery');
            $this->tags(['grocery', 'delivery', $this->correlationId]);
        }

        public function handle(
            InventoryManagementService $inventoryService,
            WalletService $walletService,
        ): void {
            try {
                DB::transaction(function () use ($inventoryService, $walletService) {
                    // Заказ должен быть на этапе доставки
                    if ($this->order->status !== 'in_transit') {
                        Log::channel('audit')->warning('Order not in transit status', [
                            'order_id' => $this->order->id,
                            'status' => $this->order->status,
                            'correlation_id' => $this->correlationId,
                        ]);
                        return;
                    }

                    // Деньги переводятся на счёт магазина (минус комиссия)
                    $payout = $this->order->total_price - $this->order->commission_amount;

                    $walletService->credit(
                        tenantId: $this->order->store->tenant_id,
                        amount: $payout,
                        type: 'grocery_payout',
                        correlationId: $this->correlationId,
                    );

                    // Финализируем заказ
                    $this->order->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);

                    Log::channel('audit')->info('Order processed and payout completed', [
                        'order_id' => $this->order->id,
                        'payout_amount' => $payout,
                        'commission_amount' => $this->order->commission_amount,
                        'correlation_id' => $this->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('ProcessOrderDeliveryJob failed', [
                    'order_id' => $this->order->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }

        public function failed(Throwable $exception): void
        {
            Log::channel('audit')->error('ProcessOrderDeliveryJob permanently failed', [
                'order_id' => $this->order->id,
                'exception' => $exception->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
        }
    }

    final class UpdateDeliverySurgeJob implements ShouldQueue
    {
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public $tries = 3;

        public function __construct(
            public int $storeId,
            public string $correlationId,
        ) {
            $this->onQueue('grocery-surge');
            $this->tags(['grocery', 'surge', $this->correlationId]);
        }

        public function handle(DeliverySlotManagementService $slotService): void
        {
            try {
                // Обновляем surge-коэффициенты для всех слотов магазина
                $slots = \App\Domains\GroceryAndDelivery\Models\DeliverySlot::where('store_id', $this->storeId)
                    ->where('start_time', '>', now())
                    ->get();

                foreach ($slots as $slot) {
                    $slotService->updateSurgeMultiplier($slot->id);
                }

                Log::channel('audit')->info('Surge coefficients updated', [
                    'store_id' => $this->storeId,
                    'slots_updated' => count($slots),
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('UpdateDeliverySurgeJob failed', [
                    'store_id' => $this->storeId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }
    }

    final class AssignDeliveryPartnerJob implements ShouldQueue
    {
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public $tries = 10;
        public $backoff = [5, 15, 30, 60, 120, 300];

        public function __construct(
            public GroceryOrder $order,
            public string $correlationId,
        ) {
            $this->onQueue('grocery-assignment');
            $this->tags(['grocery', 'assignment', $this->correlationId]);
        }

        public function handle(): void
        {
            try {
                DB::transaction(function () {
                    $service = app(\App\Domains\GroceryAndDelivery\Services\FastDeliveryService::class);

                    $partner = $service->assignDeliveryPartner($this->order, $this->correlationId);

                    // Обновляем заказ с партнёром доставки
                    $this->order->update([
                        'delivery_partner_id' => $partner->id,
                        'status' => 'in_transit',
                    ]);

                    // Отправляем уведомление партнёру (в реальном приложении - SMS/Push)
                    Log::channel('audit')->info('Delivery partner assigned', [
                        'order_id' => $this->order->id,
                        'partner_id' => $partner->id,
                        'partner_rating' => $partner->rating,
                        'correlation_id' => $this->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                if ($this->attempts() < $this->tries) {
                    Log::channel('audit')->warning('Will retry assignment', [
                        'order_id' => $this->order->id,
                        'attempt' => $this->attempts(),
                        'correlation_id' => $this->correlationId,
                    ]);
                    // Повторим позже
                    $this->release(60);
                } else {
                    Log::channel('audit')->error('AssignDeliveryPartnerJob permanently failed', [
                        'order_id' => $this->order->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $this->correlationId,
                    ]);
                }
                throw $e;
            }
        }
    }

    final class CleanupExpiredSlotBookingsJob implements ShouldQueue
    {
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public $tries = 1;

        public function __construct(
            public string $correlationId,
        ) {
            $this->onQueue('grocery-cleanup');
            $this->tags(['grocery', 'cleanup', $this->correlationId]);
        }

        public function handle(): void
        {
            try {
                DB::transaction(function () {
                    // Удаляем бронирования старше 20 минут, которые не были подтверждены
                    $expiredBookings = \App\Domains\GroceryAndDelivery\Models\SlotBooking::where('is_confirmed', false)
                        ->where('booked_at', '<', now()->subMinutes(20))
                        ->get();

                    foreach ($expiredBookings as $booking) {
                        $booking->delete();
                    }

                    Log::channel('audit')->info('Expired slot bookings cleaned up', [
                        'count' => count($expiredBookings),
                        'correlation_id' => $this->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('CleanupExpiredSlotBookingsJob failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }
    }

    final class SyncPartnerStoreInventoryJob implements ShouldQueue
    {
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public $tries = 5;

        public function __construct(
            public int $storeId,
            public string $correlationId,
        ) {
            $this->onQueue('grocery-sync');
            $this->tags(['grocery', 'sync', $this->correlationId]);
        }

        public function handle(InventoryManagementService $inventoryService): void
        {
            try {
                DB::transaction(function () use ($inventoryService) {
                    $store = \App\Domains\GroceryAndDelivery\Models\GroceryStore::findOrFail($this->storeId);

                    // Синхронизируем остатки с внешним API магазина
                    if ($store->api_provider && $store->api_token) {
                        // Это имитация; в реальности здесь вызов PartnerStoreAPIService
                        Log::channel('audit')->info('Store inventory sync initiated', [
                            'store_id' => $this->storeId,
                            'api_provider' => $store->api_provider,
                            'correlation_id' => $this->correlationId,
                        ]);
                    }
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('SyncPartnerStoreInventoryJob failed', [
                    'store_id' => $this->storeId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }
}
