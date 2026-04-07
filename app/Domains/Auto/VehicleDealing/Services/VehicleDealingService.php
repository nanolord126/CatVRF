<?php

declare(strict_types=1);

namespace App\Domains\Auto\VehicleDealing\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Auto\VehicleDealing\Models\VehicleSale;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Сервис управления продажами ТС.
 *
 * Комиссия платформы: 14%.
 * Все операции в $this->db->transaction(). FraudControlService перед каждой мутацией.
 *
 * @package App\Domains\Auto\VehicleDealing\Services
 */
final readonly class VehicleDealingService
{
    private const COMMISSION_RATE  = 0.14;
    private const RATE_LIMIT_KEY   = 'vehicle:sale';
    private const RATE_LIMIT_MAX   = 6;
    private const RATE_LIMIT_DECAY = 3600;

    public function __construct(private FraudControlService $fraud,
        private WalletService       $wallet,
        private RateLimiter         $rateLimiter,
        private LoggerInterface     $auditLogger,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Guard $guard) {

    }

    /**
     * Создать сделку продажи ТС.
     *
     * @throws \RuntimeException если превышен лимит запросов или заблокирован fraud
     */
    public function createSale(
        int    $vehicleId,
        int    $buyerId,
        string $correlationId = '',
    ): VehicleSale {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        $key = self::RATE_LIMIT_KEY . ':' . $buyerId;
        if ($this->rateLimiter->tooManyAttempts($key, self::RATE_LIMIT_MAX)) {
            throw new \RuntimeException('Превышен лимит запросов.', 429);
        }
        $this->rateLimiter->hit($key, self::RATE_LIMIT_DECAY);

        return $this->db->transaction(function () use ($vehicleId, $buyerId, $correlationId): VehicleSale {
            $vehicle = \App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Vehicle::findOrFail($vehicleId);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vehicle_sale', amount: 0, correlationId: $correlationId ?? '');

            if (($fraudResult['decision'] ?? 'allow') === 'block') {
                throw new \RuntimeException('Операция заблокирована системой безопасности.', 403);
            }

            $total  = $vehicle->price_kopecks ?? 0;
            $payout = (int) ($total * (1 - self::COMMISSION_RATE));

            $sale = VehicleSale::create([
                'uuid'           => Uuid::uuid4()->toString(),
                'tenant_id'      => tenant()->id,
                'vehicle_id'     => $vehicleId,
                'buyer_id'       => $buyerId,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $payout,
                'payment_status' => 'pending',
                'tags'           => ['vehicle' => true],
            ]);

            $this->auditLogger->info('Vehicle sale created', [
                'sale_id'        => $sale->id,
                'vehicle_id'     => $vehicleId,
                'correlation_id' => $correlationId,
            ]);

            return $sale;
        });
    }

    /**
     * Завершить сделку и зачислить выплату.
     */
    public function completeSale(int $saleId, string $correlationId = ''): VehicleSale
    {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($saleId, $correlationId): VehicleSale {
            $sale = VehicleSale::findOrFail($saleId);

            if ($sale->payment_status !== 'completed') {
                throw new \RuntimeException('Сделка не оплачена.', 400);
            }

            $sale->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $vehicle = \App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Vehicle::findOrFail($sale->vehicle_id);
            $vehicle->update(['status' => 'sold']);

            $this->wallet->credit(
                tenantId: tenant()->id,
                amount: $sale->payout_kopecks,
                type: 'vehicle_sale_payout',
                meta: [
                    'sale_id'        => $sale->id,
                    'correlation_id' => $correlationId,
                ],
            );

            return $sale;
        });
    }

    /**
     * Отменить сделку с возвратом.
     */
    public function cancelSale(int $saleId, string $correlationId = ''): VehicleSale
    {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($saleId, $correlationId): VehicleSale {
            $sale = VehicleSale::findOrFail($saleId);

            if ($sale->status === 'completed') {
                throw new \RuntimeException('Нельзя отменить завершённую сделку.', 400);
            }

            $sale->update([
                'status'         => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($sale->payment_status === 'completed') {
                $this->wallet->credit(
                    tenantId: tenant()->id,
                    amount: $sale->total_kopecks,
                    type: \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,
                    meta: [
                        'sale_id'        => $sale->id,
                        'correlation_id' => $correlationId,
                    ],
                );
            }

            return $sale;
        });
    }

    /**
     * Получить сделку по id.
     */
    public function getSale(int $saleId): VehicleSale
    {
        return VehicleSale::findOrFail($saleId);
    }

    /**
     * Получить сделки пользователя (10 последних).
     */
    public function getUserSales(int $buyerId): \Illuminate\Support\Collection
    {
        return VehicleSale::where('buyer_id', $buyerId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}
