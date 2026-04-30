<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * PeerReviewStatus — Enum статуса peer review
 */
enum PeerReviewStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Archived = 'archived';
}
