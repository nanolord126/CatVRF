<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Size Exchange / Rental Service для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Обмен размеров, аренда одежды, управление подписками,
        отслеживание возвратов, расчет стоимости аренды.
 */
final readonly class FashionSizeExchangeService
{
    private const EXCHANGE_PERIOD_DAYS = 14;
    private const RENTAL_PERIOD_DAYS = 30;
    private const MAX_RENTAL_ITEMS = 5;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Запросить обмен размера.
     */
    public function requestSizeExchange(
        int $userId,
        int $orderId,
        int $productId,
        string $currentSize,
        string $requestedSize,
        string $reason = '',
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_size_exchange_request',
            amount: 0,
            correlationId: $correlationId
        );

        $order = $this->db->table('orders')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDays(self::EXCHANGE_PERIOD_DAYS))
            ->first();

        if ($order === null) {
            throw new \InvalidArgumentException('Order not eligible for exchange', 400);
        }

        $exchangeId = $this->db->table('fashion_size_exchanges')->insertGetId([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'order_id' => $orderId,
            'product_id' => $productId,
            'current_size' => $currentSize,
            'requested_size' => $requestedSize,
            'reason' => $reason,
            'status' => 'pending',
            'requested_at' => Carbon::now(),
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->audit->record(
            action: 'fashion_size_exchange_requested',
            subjectType: 'fashion_size_exchange',
            subjectId: $exchangeId,
            oldValues: [],
            newValues: [
                'user_id' => $userId,
                'order_id' => $orderId,
                'current_size' => $currentSize,
                'requested_size' => $requestedSize,
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion size exchange requested', [
            'exchange_id' => $exchangeId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return [
            'exchange_id' => $exchangeId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'current_size' => $currentSize,
            'requested_size' => $requestedSize,
            'status' => 'pending',
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать аренду товара.
     */
    public function createRental(
        int $userId,
        int $productId,
        int $rentalDays = self::RENTAL_PERIOD_DAYS,
        ?string $pickupDate = null,
        ?string $returnDate = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_rental_create',
            amount: 0,
            correlationId: $correlationId
        );

        $activeRentals = $this->db->table('fashion_rentals')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        if ($activeRentals >= self::MAX_RENTAL_ITEMS) {
            throw new \RuntimeException('Maximum rental items reached', 400);
        }

        $product = $this->db->table('fashion_products')
            ->where('id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('is_rentable', true)
            ->where('status', 'active')
            ->first();

        if ($product === null) {
            throw new \InvalidArgumentException('Product not available for rental', 404);
        }

        $rentalPrice = $this->calculateRentalPrice($product['price_b2c'], $rentalDays);
        $deposit = $this->calculateDeposit($product['price_b2c']);

        $rentalId = $this->db->table('fashion_rentals')->insertGetId([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'rental_days' => $rentalDays,
            'rental_price' => $rentalPrice,
            'deposit' => $deposit,
            'pickup_date' => $pickupDate ? Carbon::parse($pickupDate) : Carbon::now(),
            'return_date' => $returnDate ? Carbon::parse($returnDate) : Carbon::now()->addDays($rentalDays),
            'status' => 'active',
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->audit->record(
            action: 'fashion_rental_created',
            subjectType: 'fashion_rental',
            subjectId: $rentalId,
            oldValues: [],
            newValues: [
                'user_id' => $userId,
                'product_id' => $productId,
                'rental_price' => $rentalPrice,
                'deposit' => $deposit,
            ],
            correlationId: $correlationId
        );

        return [
            'rental_id' => $rentalId,
            'user_id' => $userId,
            'product_id' => $productId,
            'rental_price' => $rentalPrice,
            'deposit' => $deposit,
            'pickup_date' => $pickupDate,
            'return_date' => $returnDate,
            'status' => 'active',
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Вернуть арендованный товар.
     */
    public function returnRentalItem(
        int $rentalId,
        int $userId,
        ?string $condition = null,
        ?array $damagePhotos = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $rental = $this->db->table('fashion_rentals')
            ->where('id', $rentalId)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        if ($rental === null) {
            throw new \InvalidArgumentException('Rental not found or already returned', 404);
        }

        $this->db->table('fashion_rentals')
            ->where('id', $rentalId)
            ->update([
                'status' => 'returned',
                'return_date' => Carbon::now(),
                'condition' => $condition,
                'damage_photos' => json_encode($damagePhotos),
                'updated_at' => Carbon::now(),
            ]);

        $penalty = $this->calculateReturnPenalty($rental, $condition);
        $depositRefund = $rental['deposit'] - $penalty;

        $this->audit->record(
            action: 'fashion_rental_returned',
            subjectType: 'fashion_rental',
            subjectId: $rentalId,
            oldValues: [],
            newValues: [
                'condition' => $condition,
                'penalty' => $penalty,
                'deposit_refund' => $depositRefund,
            ],
            correlationId: $correlationId
        );

        return [
            'rental_id' => $rentalId,
            'status' => 'returned',
            'penalty' => $penalty,
            'deposit_refund' => $depositRefund,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать подписку на аренду.
     */
    public function createRentalSubscription(
        int $userId,
        int $itemsPerMonth,
        int $durationMonths,
        string $planType = 'standard',
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_rental_subscription',
            amount: 0,
            correlationId: $correlationId
        );

        $monthlyPrice = $this->calculateSubscriptionPrice($itemsPerMonth, $planType);
        $totalPrice = $monthlyPrice * $durationMonths;

        $subscriptionId = $this->db->table('fashion_rental_subscriptions')->insertGetId([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'items_per_month' => $itemsPerMonth,
            'duration_months' => $durationMonths,
            'plan_type' => $planType,
            'monthly_price' => $monthlyPrice,
            'total_price' => $totalPrice,
            'status' => 'active',
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths($durationMonths),
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return [
            'subscription_id' => $subscriptionId,
            'user_id' => $userId,
            'items_per_month' => $itemsPerMonth,
            'duration_months' => $durationMonths,
            'plan_type' => $planType,
            'monthly_price' => $monthlyPrice,
            'total_price' => $totalPrice,
            'status' => 'active',
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить статистику аренды пользователя.
     */
    public function getRentalStats(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $totalRentals = $this->db->table('fashion_rentals')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->count();

        $activeRentals = $this->db->table('fashion_rentals')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        $totalSpent = $this->db->table('fashion_rentals')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->sum('rental_price');

        $pendingReturns = $this->db->table('fashion_rentals')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('return_date', '<', Carbon::now()->addDays(3))
            ->count();

        return [
            'user_id' => $userId,
            'total_rentals' => $totalRentals,
            'active_rentals' => $activeRentals,
            'total_spent' => $totalSpent,
            'pending_returns' => $pendingReturns,
            'correlation_id' => $correlationId,
        ];
    }

    private function calculateRentalPrice(float $productPrice, int $days): float
    {
        $dailyRate = $productPrice * 0.03;
        return round($dailyRate * $days, 2);
    }

    private function calculateDeposit(float $productPrice): float
    {
        return round($productPrice * 0.2, 2);
    }

    private function calculateReturnPenalty(array $rental, ?string $condition): float
    {
        if ($condition === 'damaged') {
            return round($rental['deposit'] * 0.5, 2);
        }

        if (Carbon::parse($rental['return_date'])->lt(Carbon::now())) {
            $daysLate = Carbon::now()->diffInDays(Carbon::parse($rental['return_date']));
            return round($daysLate * 10, 2);
        }

        return 0.0;
    }

    private function calculateSubscriptionPrice(int $itemsPerMonth, string $planType): float
    {
        $basePrice = $itemsPerMonth * 100;

        return match ($planType) {
            'premium' => $basePrice * 1.5,
            'standard' => $basePrice,
            'basic' => $basePrice * 0.7,
            default => $basePrice,
        };
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
