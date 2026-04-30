<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

/**
 * Metric Type Value Object
 *
 * Represents the type of metric being tracked (e.g., orders, revenue, users, etc.).
 * This is a domain value object - immutable and type-safe.
 */
final readonly class MetricType
{
    private const string ORDERS_COUNT = 'orders.count';
    private const string ORDERS_REVENUE = 'orders.revenue';
    private const string ORDERS_AOV = 'orders.aov';
    private const string USERS_ACTIVE = 'users.active';
    private const string USERS_NEW = 'users.new';
    private const string USERS_RETURNING = 'users.returning';
    private const string PRODUCTS_VIEWED = 'products.viewed';
    private const string PRODUCTS_ADDED_TO_CART = 'products.added_to_cart';
    private const string PRODUCTS_PURCHASED = 'products.purchased';
    private const string SELLERS_ACTIVE = 'sellers.active';
    private const string SELLERS_NEW = 'sellers.new';
    private const string CONVERSION_RATE = 'conversion.rate';
    private const string CART_ABANDONMENT = 'cart.abandonment';
    private const string SESSIONS = 'sessions.count';
    private const string PAGE_VIEWS = 'page_views.count';
    private const string GMV = 'gmv.total';
    private const string REFUNDS = 'refunds.count';
    private const string REFUNDS_AMOUNT = 'refunds.amount';

    /**
     * @var array<string, string>
     */
    private const array ALL_TYPES = [
        self::ORDERS_COUNT,
        self::ORDERS_REVENUE,
        self::ORDERS_AOV,
        self::USERS_ACTIVE,
        self::USERS_NEW,
        self::USERS_RETURNING,
        self::PRODUCTS_VIEWED,
        self::PRODUCTS_ADDED_TO_CART,
        self::PRODUCTS_PURCHASED,
        self::SELLERS_ACTIVE,
        self::SELLERS_NEW,
        self::CONVERSION_RATE,
        self::CART_ABANDONMENT,
        self::SESSIONS,
        self::PAGE_VIEWS,
        self::GMV,
        self::REFUNDS,
        self::REFUNDS_AMOUNT,
    ];

    public function __construct(
        public readonly string $value
    ) {
        if (!in_array($value, self::ALL_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid metric type: {$value}");
        }
    }

    public static function ordersCount(): self
    {
        return new self(self::ORDERS_COUNT);
    }

    public static function ordersRevenue(): self
    {
        return new self(self::ORDERS_REVENUE);
    }

    public static function ordersAOV(): self
    {
        return new self(self::ORDERS_AOV);
    }

    public static function usersActive(): self
    {
        return new self(self::USERS_ACTIVE);
    }

    public static function usersNew(): self
    {
        return new self(self::USERS_NEW);
    }

    public static function usersReturning(): self
    {
        return new self(self::USERS_RETURNING);
    }

    public static function productsViewed(): self
    {
        return new self(self::PRODUCTS_VIEWED);
    }

    public static function productsAddedToCart(): self
    {
        return new self(self::PRODUCTS_ADDED_TO_CART);
    }

    public static function productsPurchased(): self
    {
        return new self(self::PRODUCTS_PURCHASED);
    }

    public static function sellersActive(): self
    {
        return new self(self::SELLERS_ACTIVE);
    }

    public static function sellersNew(): self
    {
        return new self(self::SELLERS_NEW);
    }

    public static function conversionRate(): self
    {
        return new self(self::CONVERSION_RATE);
    }

    public static function cartAbandonment(): self
    {
        return new self(self::CART_ABANDONMENT);
    }

    public static function sessions(): self
    {
        return new self(self::SESSIONS);
    }

    public static function pageViews(): self
    {
        return new self(self::PAGE_VIEWS);
    }

    public static function gmv(): self
    {
        return new self(self::GMV);
    }

    public static function refunds(): self
    {
        return new self(self::REFUNDS);
    }

    public static function refundsAmount(): self
    {
        return new self(self::REFUNDS_AMOUNT);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function isFinancial(): bool
    {
        return str_contains($this->value, 'revenue') ||
               str_contains($this->value, 'gmv') ||
               str_contains($this->value, 'aov') ||
               str_contains($this->value, 'refunds.amount');
    }

    public function isCount(): bool
    {
        return str_ends_with($this->value, '.count');
    }

    public function isRate(): bool
    {
        return str_ends_with($this->value, '.rate') ||
               str_ends_with($this->value, '.abandonment');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
