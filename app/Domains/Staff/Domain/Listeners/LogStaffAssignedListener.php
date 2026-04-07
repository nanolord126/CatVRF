<?php declare(strict_types=1);

namespace App\Domains\Staff\Domain\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Staff\Domain\Events\StaffAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class LogStaffAssignedListener
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
final class LogStaffAssignedListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(StaffAssigned $event): void
    {
        $this->logger->info('LogStaffAssignedListener handled', [
            'event' => 'StaffAssigned',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(StaffAssigned $event, \Throwable $exception): void
    {
        $this->logger->error('LogStaffAssignedListener failed', [
            'event' => 'StaffAssigned',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}