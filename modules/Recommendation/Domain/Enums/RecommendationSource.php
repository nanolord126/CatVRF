<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Enums;

enum RecommendationSource: string
{
    case TWO_TOWER = 'two_tower';
    case COLLABORATIVE = 'collaborative';
    case CONTENT_BASED = 'content_based';
    case GRAPH_BASED = 'graph_based';
    case BANDIT = 'bandit';
    case RULE = 'rule';
    case POPULARITY = 'popularity';
    case FREQUENTLY_BOUGHT = 'frequently_bought';
    case SIMILAR_ITEMS = 'similar_items';
    case TRENDING = 'trending';
    case SELLER_PROMO = 'seller_promo';
    case FALLBACK = 'fallback';

    public function isMLBased(): bool
    {
        return match ($this) {
            self::TWO_TOWER, self::COLLABORATIVE, self::CONTENT_BASED,
            self::GRAPH_BASED, self::BANDIT => true,
            default => false,
        };
    }

    public function requiresInference(): bool
    {
        return match ($this) {
            self::TWO_TOWER, self::COLLABORATIVE, self::BANDIT => true,
            default => false,
        };
    }
}
