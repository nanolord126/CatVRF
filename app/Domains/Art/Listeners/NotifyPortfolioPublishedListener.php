<?php declare(strict_types=1);

namespace App\Domains\Art\Listeners;



use Psr\Log\LoggerInterface;
use App\Domains\Art\Events\PortfolioItemPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class NotifyPortfolioPublishedListener
 *
 * Part of the Art vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Art\Listeners
 */
final class NotifyPortfolioPublishedListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(PortfolioItemPublished $event): void
    {
        $this->logger->info('NotifyPortfolioPublishedListener handled', [
            'event' => 'PortfolioItemPublished',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(PortfolioItemPublished $event, \Throwable $exception): void
    {
        $this->logger->error('NotifyPortfolioPublishedListener failed', [
            'event' => 'PortfolioItemPublished',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId ?? \Illuminate\Support\Str::uuid()->toString(),
        ]);
    }
}