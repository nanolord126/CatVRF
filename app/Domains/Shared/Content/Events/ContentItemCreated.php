<?php

declare(strict_types=1);

/**
 * ContentItemCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/contentitemcreated
 */

namespace App\Domains\Content\Events;

use App\Domains\Content\Models\ContentItem;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class ContentItemCreated
 *
 * Part of the Content vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class ContentItemCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly ContentItem $contentItem,
        public readonly string $correlationId
    ) {}
}
