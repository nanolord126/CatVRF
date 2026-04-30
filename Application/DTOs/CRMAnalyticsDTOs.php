<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs;

/**
 * CRM Analytics DTOs Collection
 * 
 * Consolidated small DTOs for customer analytics and top customer data.
 * This reduces file count while maintaining clear separation of concerns.
 */

final readonly class CustomerAnalyticsDTO
{
    public function __construct(
        public int $totalCustomers,
        public int $newCustomers,
        public int $activeCustomers,
        public int $sleepingCustomers,
        public int $vipCustomers,
        public float $totalRevenue,
        public int $subscriptionUsers,
        public int $returnsCount,
        public int $periodDays,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            totalCustomers: $data['total_customers'] ?? 0,
            newCustomers: $data['new_customers'] ?? 0,
            activeCustomers: $data['active_customers'] ?? 0,
            sleepingCustomers: $data['sleeping_customers'] ?? 0,
            vipCustomers: $data['vip_customers'] ?? 0,
            totalRevenue: $data['total_revenue'] ?? 0,
            subscriptionUsers: $data['subscription_users'] ?? 0,
            returnsCount: $data['returns_count'] ?? 0,
            periodDays: $data['period_days'] ?? 30,
        );
    }

    public function toArray(): array
    {
        return [
            'total_customers' => $this->totalCustomers,
            'new_customers' => $this->newCustomers,
            'active_customers' => $this->activeCustomers,
            'sleeping_customers' => $this->sleepingCustomers,
            'vip_customers' => $this->vipCustomers,
            'total_revenue' => $this->totalRevenue,
            'subscription_users' => $this->subscriptionUsers,
            'returns_count' => $this->returnsCount,
            'period_days' => $this->periodDays,
        ];
    }
}

final readonly class TopCustomerDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public int $totalSpent,
        public int $ordersCount,
        public string $loyaltyTier,
        public ?string $lastOrderAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            totalSpent: $data['total_spent'],
            ordersCount: $data['orders_count'],
            loyaltyTier: $data['loyalty_tier'],
            lastOrderAt: $data['last_order_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'total_spent' => $this->totalSpent,
            'orders_count' => $this->ordersCount,
            'loyalty_tier' => $this->loyaltyTier,
            'last_order_at' => $this->lastOrderAt,
        ];
    }
}
