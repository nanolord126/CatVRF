<?php declare(strict_types=1);

/**
 * LogBookUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logbookupdated
 */


namespace App\Domains\BooksAndLiterature\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\BooksAndLiterature\Events\BookUpdated;
/**
 * Class LogBookUpdated
 *
 * Part of the BooksAndLiterature vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\BooksAndLiterature\Listeners
 */
final class LogBookUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(BookUpdated $event): void
    {
        $this->logger->info('Book updated', [
            'model_id' => $event->book->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->book->tenant_id ?? null,
        ]);
    }
}
