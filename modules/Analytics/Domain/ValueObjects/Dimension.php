<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

/**
 * Dimension Value Object
 *
 * Represents a dimension for analytics aggregation (e.g., by category, by region, by device).
 * This is a domain value object - immutable and type-safe.
 */
final readonly class Dimension
{
    private const string CATEGORY = 'category';
    private const string SELLER = 'seller';
    private const string PRODUCT = 'product';
    private const string REGION = 'region';
    private const string CITY = 'city';
    private const string DEVICE = 'device';
    private const string OS = 'os';
    private const string BROWSER = 'browser';
    private const string TRAFFIC_SOURCE = 'traffic_source';
    private const string UTM_MEDIUM = 'utm_medium';
    private const string UTM_SOURCE = 'utm_source';
    private const string UTM_CAMPAIGN = 'utm_campaign';
    private const string PAYMENT_METHOD = 'payment_method';
    private const string DELIVERY_TYPE = 'delivery_type';
    private const string TIME_OF_DAY = 'time_of_day';
    private const string DAY_OF_WEEK = 'day_of_week';
    private const string USER_SEGMENT = 'user_segment';
    private const string PRICE_RANGE = 'price_range';
    private const string BRAND = 'brand';

    /**
     * @var array<string, string>
     */
    private const array VALID_DIMENSIONS = [
        self::CATEGORY,
        self::SELLER,
        self::PRODUCT,
        self::REGION,
        self::CITY,
        self::DEVICE,
        self::OS,
        self::BROWSER,
        self::TRAFFIC_SOURCE,
        self::UTM_MEDIUM,
        self::UTM_SOURCE,
        self::UTM_CAMPAIGN,
        self::PAYMENT_METHOD,
        self::DELIVERY_TYPE,
        self::TIME_OF_DAY,
        self::DAY_OF_WEEK,
        self::USER_SEGMENT,
        self::PRICE_RANGE,
        self::BRAND,
    ];

    /**
     * @var array<string, array<string>>
     */
    private const array DIMENSION_VALUES = [
        self::DEVICE => ['desktop', 'mobile', 'tablet'],
        self::OS => ['ios', 'android', 'windows', 'macos', 'linux'],
        self::DAY_OF_WEEK => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
        self::TIME_OF_DAY => ['morning', 'afternoon', 'evening', 'night'],
    ];

    public function __construct(
        public readonly string $value,
        public readonly ?string $filterValue = null
    ) {
        if (!in_array($value, self::VALID_DIMENSIONS, true)) {
            throw new \InvalidArgumentException("Invalid dimension: {$value}");
        }

        if ($filterValue !== null && isset(self::DIMENSION_VALUES[$value])) {
            if (!in_array($filterValue, self::DIMENSION_VALUES[$value], true)) {
                throw new \InvalidArgumentException("Invalid filter value '{$filterValue}' for dimension {$value}");
            }
        }
    }

    public static function category(?string $filterValue = null): self
    {
        return new self(self::CATEGORY, $filterValue);
    }

    public static function seller(?string $filterValue = null): self
    {
        return new self(self::SELLER, $filterValue);
    }

    public static function product(?string $filterValue = null): self
    {
        return new self(self::PRODUCT, $filterValue);
    }

    public static function region(?string $filterValue = null): self
    {
        return new self(self::REGION, $filterValue);
    }

    public static function city(?string $filterValue = null): self
    {
        return new self(self::CITY, $filterValue);
    }

    public static function device(?string $filterValue = null): self
    {
        return new self(self::DEVICE, $filterValue);
    }

    public static function os(?string $filterValue = null): self
    {
        return new self(self::OS, $filterValue);
    }

    public static function browser(?string $filterValue = null): self
    {
        return new self(self::BROWSER, $filterValue);
    }

    public static function trafficSource(?string $filterValue = null): self
    {
        return new self(self::TRAFFIC_SOURCE, $filterValue);
    }

    public static function utmMedium(?string $filterValue = null): self
    {
        return new self(self::UTM_MEDIUM, $filterValue);
    }

    public static function utmSource(?string $filterValue = null): self
    {
        return new self(self::UTM_SOURCE, $filterValue);
    }

    public static function utmCampaign(?string $filterValue = null): self
    {
        return new self(self::UTM_CAMPAIGN, $filterValue);
    }

    public static function paymentMethod(?string $filterValue = null): self
    {
        return new self(self::PAYMENT_METHOD, $filterValue);
    }

    public static function deliveryType(?string $filterValue = null): self
    {
        return new self(self::DELIVERY_TYPE, $filterValue);
    }

    public static function timeOfDay(?string $filterValue = null): self
    {
        return new self(self::TIME_OF_DAY, $filterValue);
    }

    public static function dayOfWeek(?string $filterValue = null): self
    {
        return new self(self::DAY_OF_WEEK, $filterValue);
    }

    public static function userSegment(?string $filterValue = null): self
    {
        return new self(self::USER_SEGMENT, $filterValue);
    }

    public static function priceRange(?string $filterValue = null): self
    {
        return new self(self::PRICE_RANGE, $filterValue);
    }

    public static function brand(?string $filterValue = null): self
    {
        return new self(self::BRAND, $filterValue);
    }

    public static function fromString(string $value, ?string $filterValue = null): self
    {
        return new self($value, $filterValue);
    }

    public function hasFilter(): bool
    {
        return $this->filterValue !== null;
    }

    public function isGeographic(): bool
    {
        return in_array($this->value, [self::REGION, self::CITY], true);
    }

    public function isTechnical(): bool
    {
        return in_array($this->value, [self::DEVICE, self::OS, self::BROWSER], true);
    }

    public function isMarketing(): bool
    {
        return in_array($this->value, [self::TRAFFIC_SOURCE, self::UTM_MEDIUM, self::UTM_SOURCE, self::UTM_CAMPAIGN], true);
    }

    public function isBusiness(): bool
    {
        return in_array($this->value, [self::CATEGORY, self::SELLER, self::PRODUCT, self::BRAND, self::PRICE_RANGE], true);
    }

    public function __toString(): string
    {
        if ($this->hasFilter()) {
            return "{$this->value}:{$this->filterValue}";
        }

        return $this->value;
    }
}
