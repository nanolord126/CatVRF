<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\ShortTermRentals\Events\BookingCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class LogStrBookingCompletedListener
 *
 * Part of the ShortTermRentals vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\ShortTermRentals\Listeners
 */
final class LogStrBookingCompletedListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(BookingCompleted $event): void
    {
        $this->logger->info('LogStrBookingCompletedListener handled', [
            'event' => 'BookingCompleted',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(BookingCompleted $event, \Throwable $exception): void
    {
        $this->logger->error('LogStrBookingCompletedListener failed', [
            'event' => 'BookingCompleted',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}