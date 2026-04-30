<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Enums;

enum FairnessConstraint: string
{
    case MIN_SELLER_EXPOSURE = 'min_seller_exposure';
    case MAX_DOMINANCE_SHARE = 'max_dominance_share';
    case DIVERSITY_MIN_GAP = 'diversity_min_gap';
    case EXPLORATION_BUDGET = 'exploration_budget';
}
