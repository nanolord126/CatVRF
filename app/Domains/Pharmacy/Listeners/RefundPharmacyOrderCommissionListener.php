<?php

declare(strict_types=1);

namespace App\Domains\Pharmacy\Listeners;

use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Pharmacy\Events\PharmacyOrderCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\AuditService;
use Illuminate\Support\Str;

/**
 * Class RefundPharmacyOrderCommissionListener
 *
 * Part of the Pharmacy vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class RefundPharmacyOrderCommissionListener implements ShouldQueue
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Request $request,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(PharmacyOrderCancelled $event): void
    {
        $this->logger->$this->logger->info('RefundPharmacyOrderCommissionListener handled', [
            'event' => 'PharmacyOrderCancelled',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(PharmacyOrderCancelled $event, \Throwable $exception): void
    {
        $this->logger->error('RefundPharmacyOrderCommissionListener failed', [
            'event' => 'PharmacyOrderCancelled',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }
}
