<?php

namespace Modules\Payments\Services;

use Bavix\Wallet\Models\Wallet;
use Modules\Payments\Gateways\PaymentGatewayInterface;
use Modules\Hotels\Models\Hotel;
use Modules\Beauty\Models\BeautySalon;

class PaymentService
{
    protected PaymentGatewayInterface $gateway;
    protected float $commissionPercent;
    protected \App\Services\Payments\AtolService $atolService;
    protected \Modules\Inventory\Services\InventorySyncService $inventorySync;

    public function __construct(
        PaymentGatewayInterface $gateway, 
        \App\Services\Payments\AtolService $atolService,
        \Modules\Inventory\Services\InventorySyncService $inventorySync
    ) {
        $this->gateway = $gateway;
        $this->atolService = $atolService;
        $this->inventorySync = $inventorySync;
        $this->commissionPercent = config('payments.commissions.platform_percent', 12.0);
    }

    public function processOrderPayment($owner, float $amount, string $orderId, string $email = null, array $orderItems = []): array
    {
        $correlationId = request()->header('X-Correlation-ID', bin2hex(random_bytes(16)));

        $paymentData = $this->gateway->createPayment($amount, $orderId, [
           'owner_id' => $owner->id,
           'owner_type' => get_class($owner),
           'correlation_id' => $correlationId
        ]);

        if (($paymentData['Success'] ?? false) && $email) {
            // 1. Фискализация чека в АТОЛ
            $atolItems = collect($orderItems)->map(function($item) {
                return [
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity']
                ];
            })->toArray();
            
            if (empty($atolItems)) {
                $atolItems = [['name' => "Оплата заказа #{$orderId}", 'price' => $amount, 'quantity' => 1]];
            }

            $receiptUuid = $this->atolService->registerSell($orderId, $email, $atolItems, $amount);
            
            // 2. Списание со склада (если это товары из Inventory)
            foreach ($orderItems as $item) {
                if (isset($item['product_id'])) {
                    $this->inventorySync->deductStock(
                        $item['product_id'], 
                        $item['quantity'], 
                        'Order', 
                        $orderId
                    );
                }
            }

            if ($receiptUuid) {
                 Log::info('Payment-to-Services Sync OK', [
                     'order_id' => $orderId,
                     'receipt_uuid' => $receiptUuid,
                     'correlation_id' => $correlationId
                 ]);
            }
        }

        return $paymentData;
    }

    public function splitPayment($owner, $client, float $amount): void
    {
        // Check if trial is active or paid
        if (!$owner->is_paid && now()->greaterThan($owner->trial_ends_at)) {
             throw new \Exception("Subscription expired. Please pay 15,000 RUB fee.");
        }

        $platformPercent = config('payments.commissions.platform_percent', 12.0);
        $cashbackPercent = config('payments.commissions.client_cashback_percent', 1.0);

        // Total platform cut (12%)
        $totalCommission = $amount * ($platformPercent / 100);
        
        // Bonus to client (1% of the original amount, taken from the 12% commission)
        $clientBonus = $amount * ($cashbackPercent / 100);
        
        // Actual platform net profit (11%)
        $platformNet = $totalCommission - $clientBonus;

        // Owner (Hotel/Salon) gets their 88%
        $ownerAmount = $amount - $totalCommission;
        $owner->deposit($ownerAmount, ['type' => 'order_revenue', 'order_id' => '...']);

        // Client gets their 1% cashback in bonus wallet
        $client->deposit($clientBonus, ['description' => 'Кэшбэк 1% от заказа', 'type' => 'cashback']);

        // Record platform commission history
        // PlatformAdmin::getCentralWallet()->deposit($platformNet);
    }

    public function handleOnboardingPayment($owner): void
    {
        $fee = config('payments.onboarding.total_fee', 15000);
        $license = config('payments.onboarding.license_fee', 7500);
        $deposit = config('payments.onboarding.deposit_amount', 7500);

        // 1. Process via Gateway first (pseudo-code)
        // $this->gateway->createPayment($fee, 'onboarding_' . $owner->id);

        // 2. If Success:
        $owner->update(['is_paid' => true]);
        
        // 3. 7500 goes to security deposit (reserve balance)
        $owner->deposit($deposit, [
            'type' => 'security_deposit', 
            'description' => 'Гарантийный депозит (невозвратный при закрытии бизнеса)',
            'refundable' => false
        ]);

        // 4. 7500 stays with platform as license fee
        // PlatformAdmin::getCentralWallet()->deposit($license);
    }
}
