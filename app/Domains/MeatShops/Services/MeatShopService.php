<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class MeatShopService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создание мясного магазина/лавки на платформе.
     */
    public function createShop(
        int $tenantId,
        string $shopName,
        string $address,
        string $licenseNumber,
        array $specializations,
        string $correlationId,
    ): MeatShop {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'meat_shop_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($tenantId, $shopName, $address, $licenseNumber, $specializations, $correlationId): MeatShop {
            $shop = MeatShop::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'owner_id' => $this->guard->id(),
                'name' => $shopName,
                'address' => $address,
                'license_number' => $licenseNumber,
                'specializations' => $specializations,
                'is_active' => true,
                'rating' => 5.0,
                'correlation_id' => $correlationId,
                'tags' => ['type' => 'meat_shop', 'verified' => false],
            ]);

            $this->logger->info('Meat shop created', [
                'shop_id' => $shop->id,
                'shop_uuid' => $shop->uuid,
                'tenant_id' => $tenantId,
                'license' => $licenseNumber,
                'correlation_id' => $correlationId,
            ]);

            return $shop;
        });
    }

    /**
     * Обновление ассортимента мясного магазина (добавление нового продукта).
     */
    public function addProduct(
        int $shopId,
        string $productName,
        string $animalType,
        string $cutType,
        int $pricePerGram,
        int $stockGrams,
        string $correlationId,
    ): MeatProduct {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'meat_product_add',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($shopId, $productName, $animalType, $cutType, $pricePerGram, $stockGrams, $correlationId): MeatProduct {
            $shop = MeatShop::findOrFail($shopId);

            $product = MeatProduct::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $shop->tenant_id,
                'shop_id' => $shop->id,
                'name' => $productName,
                'animal_type' => $animalType,
                'cut_type' => $cutType,
                'price_per_gram' => $pricePerGram,
                'stock_grams' => $stockGrams,
                'is_available' => $stockGrams > 0,
                'correlation_id' => $correlationId,
                'tags' => ['animal' => $animalType, 'cut' => $cutType],
            ]);

            $this->logger->info('Meat product added to shop', [
                'product_id' => $product->id,
                'shop_id' => $shopId,
                'animal_type' => $animalType,
                'stock_grams' => $stockGrams,
                'correlation_id' => $correlationId,
            ]);

            return $product;
        });
    }

    /**
     * Завершение заказа и выплата мясному магазину (86% после комиссии).
     */
    public function completeOrderPayout(int $orderId, string $correlationId): void
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'meat_shop_payout',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($orderId, $correlationId): void {
            $order = MeatOrder::with('shop')->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'delivered') {
                throw new \RuntimeException("Order {$orderId} must be delivered before payout.");
            }

            $totalAmount = $order->total_price_kopecks;
            $platformFee = (int) ($totalAmount * 0.14);
            $payoutAmount = $totalAmount - $platformFee;

            $this->wallet->credit(
                userId: $order->shop->owner_id,
                amount: $payoutAmount,
                type: 'meat_shop_payout',
                reason: "Payout for meat order #{$order->id}",
                correlationId: $correlationId,
            );

            $order->update([
                'status' => 'paid_out',
                'payout_amount' => $payoutAmount,
                'platform_fee' => $platformFee,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Meat shop payout completed', [
                'order_id' => $order->id,
                'payout_amount' => $payoutAmount,
                'platform_fee' => $platformFee,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Получение списка активных магазинов для тенанта.
     */
    public function getActiveShops(int $tenantId): \Illuminate\Support\Collection
    {
        return MeatShop::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('rating')
            ->get();
    }
}
