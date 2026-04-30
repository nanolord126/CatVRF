<?php

declare(strict_types=1);

/**
 * BookCreated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/bookcreated
 */

namespace App\Domains\BooksAndLiterature\Events;

use App\Domains\BooksAndLiterature\Models\Book;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class BookCreated
 *
 * Part of the BooksAndLiterature vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class BookCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Book $book,
        public readonly string $correlationId
    ) {}
}
