<?php declare(strict_types=1);

/**
 * BookUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/bookupdated
 */


namespace App\Domains\BooksAndLiterature\Events;

use App\Domains\BooksAndLiterature\Models\Book;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class BookUpdated
 *
 * Part of the BooksAndLiterature vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\BooksAndLiterature\Events
 */
final class BookUpdated
{

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Book $book,
        public readonly string $correlationId) {}
}
