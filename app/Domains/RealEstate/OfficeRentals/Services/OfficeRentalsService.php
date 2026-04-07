<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\OfficeRentals\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\RealEstate\OfficeRentals\Models\CoworkingRental;
use App\Domains\RealEstate\OfficeRentals\Models\CoworkingSpace;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Сервис аренды офисных/коворкинг-пространств.
 *
 * Единственная точка создания, активации и отмены аренды офисов.
 * Комиссия платформы: 14%.
 * Все мутации — в $this->db->transaction().
 * $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') перед каждой мутацией.
 * Выплата владельцу: после статуса 'active' через WalletService::credit().
 */
final readonly class OfficeRentalsService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'office:rental';
    private const RATE_LIMIT_MAX = 7;
    private const RATE_LIMIT_TTL = 3600;

    public function __construct(private readonly FraudControlService  $fraud,
        private readonly WalletService        $wallet,
        private readonly RateLimiter          $rateLimiter,
        private readonly LoggerInterface      $logger,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Guard $guard) {}

    /**
     * Создать аренду офисного/коворкинг-пространства.
     *
     * @throws \RuntimeException если rate limit превышен или fraud-блок
     */
    public function createRental(
        int    $spaceId,
        string $leaseStart,
        string $leaseEnd,
        int    $seatsBooked,
        int    $monthCount,
        string $correlationId = '',
    ): CoworkingRental {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        $key = self::RATE_LIMIT_KEY . ':' . tenant()->id;
        if ($this->rateLimiter->tooManyAttempts($key, self::RATE_LIMIT_MAX)) {
            throw new \RuntimeException('Too many office rental requests', 429);
        }
        $this->rateLimiter->hit($key, self::RATE_LIMIT_TTL);

        return $this->db->transaction(function () use ($spaceId, $leaseStart, $leaseEnd, $seatsBooked, $monthCount, $correlationId): CoworkingRental {
            /** @var CoworkingSpace $space */
            $space = CoworkingSpace::findOrFail($spaceId);

            $seatsTotal = max(1, (int) $space->seats_count);
            $total = (int) (($space->price_kopecks_per_month / $seatsTotal) * $seatsBooked * $monthCount);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'office_rental', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Подозрение на мошенничество', 403);
            }

            $payout = $total - (int) ($total * self::COMMISSION_RATE);

            $rental = CoworkingRental::create([
                'uuid'                => Uuid::uuid4()->toString(),
                'tenant_id'           => tenant()->id,
                'space_id'            => $spaceId,
                'tenant_business_id'  => (int) $this->guard->id(),
                'correlation_id'      => $correlationId,
                'status'              => 'pending_payment',
                'total_kopecks'       => $total,
                'payout_kopecks'      => $payout,
                'payment_status'      => 'pending',
                'lease_start'         => $leaseStart,
                'lease_end'           => $leaseEnd,
                'seats_booked'        => $seatsBooked,
                'tags'                => ['office' => true],
            ]);

            $this->logger->info('Coworking rental created', [
                'rental_id'      => $rental->id,
                'space_id'       => $spaceId,
                'seats_booked'   => $seatsBooked,
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
    public function completeRental(int $rentalId, string $correlationId = ''): CoworkingRental
    {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($rentalId, $correlationId): CoworkingRental {
            $rental = CoworkingRental::findOrFail($rentalId);

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
                type: 'office_payout',
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
                    type: 'office_refund',
                    meta: ['correlation_id' => $correlationId, \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, [
                'rental_id'      => $rental->id,
                \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $tenantBusinessId, null, null, null)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}
