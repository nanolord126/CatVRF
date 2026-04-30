# Пример интеграции централизованного Payment домена в вертикали

## Фискализация чеков по 54-ФЗ (OFD)

### Пример фискализации при успешном платеже:

```php
use App\Domains\Payment\DTOs\FiscalReceiptDto;
use App\Domains\Payment\DTOs\FiscalReceiptItemDto;

public function processSuccessfulPayment(
    PaymentRecord $paymentRecord,
    string $correlationId,
): void {
    $order = GroceryOrder::where('uuid', $paymentRecord->metadata['order_uuid'] ?? null)
        ->firstOrFail();

    // Подготовить данные для фискального чека
    $receiptItems = $order->orderItems->map(fn ($item) => new FiscalReceiptItemDto(
        name: $item->product->name,
        price: (int) ($item->price_per_unit * 100), // в копейках
        quantity: $item->quantity,
        amount: (int) ($item->total_price * 100), // в копейках
        vat: 'none', // или vat10, vat20, etc.
        paymentType: 4, // 4 = полная оплата
    ))->toArray();

    $fiscalReceiptDto = new FiscalReceiptDto(
        type: 'sell', // тип чека
        taxationType: config('verticals.payment.ofd.default_taxation_type', 'usn_income'),
        totalAmount: (int) ($order->total_price * 100), // в копейках
        items: $receiptItems,
        paymentType: 2, // 2 = электронная оплата
        customerEmail: $order->user->email,
        customerPhone: $order->user->phone,
        metadata: [
            'order_id' => $order->id,
            'order_uuid' => $order->uuid,
            'vertical' => 'supermarket',
        ],
    );

    // Выполнить capture с фискализацией
    $gateway = $this->gatewayFactory->make($paymentRecord->provider_code);

    $this->paymentCoordinator->capture(
        paymentRecordId: $paymentRecord->id,
        gateway: $gateway,
        correlationId: $correlationId,
        fiscalReceiptDto: $fiscalReceiptDto, // ← автоматическая фискализация
    );

    // Разделить платеж между продавцами
    $splits = collect([
        [
            'seller_id' => $order->store->tenant_id,
            'amount_kopecks' => $paymentRecord->amount_kopecks,
            'item_ids' => $order->orderItems->pluck('product_id')->toArray(),
        ],
    ]);

    $this->splitPayment->processSplit(
        paymentRecord: $paymentRecord,
        splits: $splits,
        correlationId: $correlationId,
    );
}
```

### Конфигурация (.env):

```env
# ОФД (54-ФЗ)
PAYMENT_OFD_ENABLED=true
PAYMENT_OFD_PROVIDER=tensor
OFD_TENSOR_INN=your_inn
OFD_TENSOR_API_TOKEN=your_token
OFD_TENSOR_KKT_REG_NUMBER=your_kkt_reg_number

# Для Контур
OFD_KONTUR_API_KEY=your_api_key
OFD_KONTUR_KKT_REG_NUMBER=your_kkt_reg_number
```

## Интеграция в Supermarket (GroceryOrderService)

### Было (старый подход с WalletService):

```php
// app/Domains/Supermarket/SubVerticals/GroceryAndDelivery/Services/GroceryOrderService.php

public function completeOrder(GroceryOrder $order, string $correlationId): GroceryOrder
{
    return $this->db->transaction(function () use ($order, $correlationId): GroceryOrder {
        // ... deduct stock ...

        $payoutAmount = $order->total_price - $order->commission_amount;

        $this->wallet->credit(
            tenantId: $order->store()->first()->tenant_id,
            amount: $payoutAmount,
            reason: 'order_payout',
            correlationId: $correlationId,
        );

        // ... update order status ...
    });
}
```

### Стало (новый подход с централизованным Payment доменом):

```php
<?php

declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Services;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Models\PaymentRecord;
use App\Domains\Payment\Services\PaymentCoordinatorService;
use App\Domains\Payment\Services\SplitPaymentService;
use App\Domains\Payment\Services\Gateways\PaymentGatewayFactory;
use App\Domains\GroceryAndDelivery\Models\GroceryOrder;
use App\Services\FraudControlService;
use App\Services\Inventory\InventoryManagementService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис управления заказами вертикали GroceryAndDelivery.
 *
 * ИНТЕГРАЦИЯ С ЦЕНТРАЛИЗОВАННЫМ PAYMENT ДОМЕНОМ:
 * - PaymentCoordinatorService для создания платежей через шлюзы
 * - SplitPaymentService для автоматического разделения комиссии
 * - PayoutService для трекинга выплат продавцам
 */
final readonly class GroceryOrderService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly InventoryManagementService $inventory,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly PaymentCoordinatorService $paymentCoordinator,
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly SplitPaymentService $splitPayment,
    ) {}

    /**
     * Создать платёж для заказа через централизованный Payment домен.
     *
     * @return array{payment_record: PaymentRecord, redirect_url: string}
     */
    public function createOrderPayment(
        GroceryOrder $order,
        string $paymentMethod = 'card',
        string $correlationId = null,
    ): array {
        $correlationId ??= (string) Str::uuid();

        // Получить провайдер из конфига вертикали
        $provider = $this->getProviderForPaymentMethod('supermarket', $paymentMethod);

        // Создать DTO для платежа
        $dto = new CreatePaymentRecordDto(
            tenantId: $order->tenant_id,
            businessGroupId: null,
            providerCode: $provider->value,
            amountKopecks: (int) ($order->total_price * 100),
            idempotencyKey: 'grocery_order_'.$order->uuid,
            correlationId: $correlationId,
            isHold: false,
            description: "Заказ в супермаркете #{$order->uuid}",
            metadata: [
                'vertical' => 'supermarket',
                'sub_vertical' => 'grocery_and_delivery',
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'payment_method' => $paymentMethod,
            ],
        );

        // Получить шлюз через фабрику
        $gateway = $this->gatewayFactory->make($provider);

        // Инициировать платёж через координатор (с fraud check)
        return $this->paymentCoordinator->initPayment(
            dto: $dto,
            gateway: $gateway,
            verticalCode: 'supermarket',
            subVerticalCode: 'grocery_and_delivery',
            urgencyLevel: 'normal',
            isEmergency: false,
        );
    }

    /**
     * Обработать успешный платёж и разделить комиссию.
     *
     * Вызывается после успешного webhook от платёжного шлюза.
     */
    public function processSuccessfulPayment(
        PaymentRecord $paymentRecord,
        string $correlationId,
    ): void {
        $order = GroceryOrder::where('uuid', $paymentRecord->metadata['order_uuid'] ?? null)
            ->firstOrFail();

        // Подготовить данные для разделения платежа между продавцами
        $splits = collect([
            [
                'seller_id' => $order->store->tenant_id,
                'amount_kopecks' => $paymentRecord->amount_kopecks,
                'item_ids' => $order->orderItems->pluck('product_id')->toArray(),
            ],
        ]);

        // Разделить платёж: комиссия платформы + выплата продавцу
        $result = $this->splitPayment->processSplit(
            paymentRecord: $paymentRecord,
            splits: $splits,
            correlationId: $correlationId,
        );

        $this->logger->info('Payment split processed for grocery order', [
            'order_id' => $order->id,
            'payment_record_id' => $paymentRecord->id,
            'platform_fee' => $result['platform_fee'],
            'payouts_count' => $result['payouts']->count(),
            'correlation_id' => $correlationId,
        ]);

        // Обновить статус заказа
        $order->update([
            'status' => 'paid',
            'payment_record_id' => $paymentRecord->id,
        ]);
    }

    /**
     * Получить провайдер для метода оплаты из конфига вертикали.
     */
    private function getProviderForPaymentMethod(string $vertical, string $paymentMethod): PaymentProvider
    {
        $config = config('verticals.payment');

        // Проверить, поддерживает ли вертикаль этот метод
        $allowedMethods = $config['vertical_payment_methods'][$vertical] ?? [];
        if (! in_array($paymentMethod, $allowedMethods, true)) {
            throw new \InvalidArgumentException("Payment method {$paymentMethod} not supported for vertical {$vertical}");
        }

        // Получить провайдеры для метода оплаты
        $providers = $config['payment_methods'][$paymentMethod]['providers'] ?? [];
        if (empty($providers)) {
            throw new \RuntimeException("No providers configured for payment method {$paymentMethod}");
        }

        // Вернуть первый доступный провайдер (или можно добавить логику выбора)
        return PaymentProvider::from($providers[0]);
    }
}
```

## Ключевые преимущества новой архитектуры:

1. **Централизация**: Все вертикали используют один Payment домен
2. **Fraud detection**: Встроенный в PaymentCoordinatorService
3. **Split payments**: Автоматическое разделение комиссии между продавцами
4. **Мульти-провайдерность**: Tinkoff, Sber, Tochka, СБП через единый интерфейс
5. **Config-driven**: Методы оплаты настраиваются в config/verticals.php
6. **Audit logging**: Автоматический аудит всех платежных операций

## Миграция существующих вертикалей:

1. Заменить прямые вызовы WalletService на PaymentCoordinatorService
2. Добавить вызов SplitPaymentService после успешного платежа
3. Обновить конфиг config/verticals.php с методами оплаты для вертикали
4. Добавить webhook обработчики для обновления статусов платежей

## Webhook обработчик пример:

```php
// app/Domains/Payment/Controllers/PaymentWebhookController.php

public function handle(Request $request, string $provider)
{
    $payload = $request->all();
    $signature = $request->header('X-Signature');
    $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

    $providerEnum = PaymentProvider::from($provider);
    $gateway = $this->gatewayFactory->make($providerEnum);

    $paymentRecord = $this->paymentCoordinator->handleWebhook(
        gateway: $gateway,
        payload: $payload,
        signature: $signature,
        correlationId: $correlationId,
    );

    // Если платёж успешен - триггерить логику вертикали
    if ($paymentRecord->status === PaymentStatus::CAPTURED) {
        $vertical = $paymentRecord->metadata['vertical'] ?? null;
        if ($vertical === 'supermarket') {
            app(GroceryOrderService::class)->processSuccessfulPayment(
                $paymentRecord,
                $correlationId,
            );
        }
        // Добавить обработку для других вертикалей...
    }

    return response()->json(['status' => 'ok']);
}
```
