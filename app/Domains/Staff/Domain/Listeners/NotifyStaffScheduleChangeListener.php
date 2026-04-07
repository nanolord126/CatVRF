<?php declare(strict_types=1);

namespace App\Domains\Staff\Domain\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Staff\Domain\Events\StaffScheduleChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class NotifyStaffScheduleChangeListener
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Staff\Domain\Listeners
 */
final class NotifyStaffScheduleChangeListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(StaffScheduleChanged $event): void
    {
        $this->logger->info('NotifyStaffScheduleChangeListener handled', [
            'event' => 'StaffScheduleChanged',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(StaffScheduleChanged $event, \Throwable $exception): void
    {
        $this->logger->error('NotifyStaffScheduleChangeListener failed', [
            'event' => 'StaffScheduleChanged',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}