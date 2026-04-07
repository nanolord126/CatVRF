<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\ShopRentals\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\RealEstate\ShopRentals\Models\Storefront;
use App\Domains\RealEstate\ShopRentals\Models\StorefrontRental;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Сервис аренды торговых площадей (магазинов/шоурумов).
 *
 * Единственная точка создания, активации и отмены аренды.
 * Комиссия платформы: 14%.
 * Все мутации — в $this->db->transaction().
 * $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') перед каждой мутацией.
 * Выплата владельцу: после статуса 'active' через WalletService::credit().
 */
final readonly class ShopRentalsService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'shop:rental';
    private const RATE_LIMIT_MAX = 6;
    private const RATE_LIMIT_TTL = 3600;

    public function __construct(private readonly FraudControlService  $fraud,
        private readonly WalletService        $wallet,
        private readonly RateLimiter          $rateLimiter,
        private readonly LoggerInterface      $logger,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Guard $guard) {}

    /**
     * Создать аренду торговой площади.
     *
     * @throws \RuntimeException если rate limit превышен или fraud-блок
     */
    public function createRental(
        int    $storefrontId,
        string $leaseStart,
        string $leaseEnd,
        int    $monthCount,
        string $correlationId = '',
    ): StorefrontRental {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        $key = self::RATE_LIMIT_KEY . ':' . tenant()->id;
        if ($this->rateLimiter->tooManyAttempts($key, self::RATE_LIMIT_MAX)) {
            throw new \RuntimeException('Too many shop rental requests', 429);
        }
        $this->rateLimiter->hit($key, self::RATE_LIMIT_TTL);

        return $this->db->transaction(function () use ($storefrontId, $leaseStart, $leaseEnd, $monthCount, $correlationId): StorefrontRental {
            /** @var Storefront $storefront */
            $storefront = Storefront::findOrFail($storefrontId);

            $total = (int) ($storefront->price_kopecks_per_month * $monthCount);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'shop_rental', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Подозрение на мошенничество', 403);
            }

            $payout = $total - (int) ($total * self::COMMISSION_RATE);

            $rental = StorefrontRental::create([
                'uuid'                => Uuid::uuid4()->toString(),
                'tenant_id'           => tenant()->id,
                'storefront_id'       => $storefrontId,
                'tenant_business_id'  => (int) $this->guard->id(),
                'correlation_id'      => $correlationId,
                'status'              => 'pending_payment',
                'total_kopecks'       => $total,
                'payout_kopecks'      => $payout,
                'payment_status'      => 'pending',
                'lease_start'         => $leaseStart,
                'lease_end'           => $leaseEnd,
                'tags'                => ['shop' => true],
            ]);

            $this->logger->info('Storefront rental created', [
                'rental_id'      => $rental->id,
                'storefront_id'  => $storefrontId,
                'total_kopecks'  => $total,
                'correlation_id' => $correlationId,
            ]);

            return $rental;
        });
    }

    /**
     * Активировать аренду после оплаты и начислить выплату.
     *
     * @throws \RuntimeException если оплата не завершена
     */
    public function completeRental(int $rentalId, string $correlationId = ''): StorefrontRental
    {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($rentalId, $correlationId): StorefrontRental {
            $rental = StorefrontRental::findOrFail($rentalId);

            if ($rental->payment_status !== 'completed') {
                throw new \RuntimeException('Оплата не подтверждена', 400);
            }

            $rental->update([
                'status'         => 'active',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                tenantId: tenant()->id,
                amount: $rental->payout_kopecks,
                type: 'shop_payout',
                meta: ['correlation_id' => $correlationId, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, [
                'rental_id'      => $rental->id,
                \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, 400, null, null, null);
            }

            $rental->update([
                'status'         => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($rental->payment_status === 'completed') {
                $this->wallet->credit(
                    tenantId: tenant()->id,
                    amount: $rental->total_kopecks,
                    type: 'shop_refund',
                    meta: ['correlation_id' => $correlationId, \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, [
                'rental_id'      => $rental->id,
                \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $tenantBusinessId, null, null, null)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}
