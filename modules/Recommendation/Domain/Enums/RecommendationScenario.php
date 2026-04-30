<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Enums;

enum RecommendationScenario: string
{
    case HOME_FEED = 'home_feed';
    case PRODUCT_DETAIL = 'product_detail';
    case CART = 'cart';
    case SEARCH = 'search';
    case SELLER_PAGE = 'seller_page';
    case EMAIL_DIGEST = 'email_digest';
    case CHECKOUT_UPSELL = 'checkout_upsell';
    case CATEGORY_BROWSE = 'category_browse';
    case REORDER = 'reorder';

    public function defaultLimit(): int
    {
        return match ($this) {
            self::HOME_FEED => 20,
            self::PRODUCT_DETAIL => 12,
            self::CART => 6,
            self::SEARCH => 30,
            self::SELLER_PAGE => 16,
            self::EMAIL_DIGEST => 8,
            self::CHECKOUT_UPSELL => 4,
            self::CATEGORY_BROWSE => 24,
            self::REORDER => 10,
        };
    }

    public function cacheTtl(): int
    {
        return match ($this) {
            self::HOME_FEED, self::PRODUCT_DETAIL => 300,
            self::CART, self::CHECKOUT_UPSELL => 60,
            self::SEARCH => 120,
            self::SELLER_PAGE => 600,
            self::EMAIL_DIGEST => 3600,
            self::CATEGORY_BROWSE => 180,
            self::REORDER => 900,
        };
    }
}
