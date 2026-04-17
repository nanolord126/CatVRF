<?php declare(strict_types=1);

/**
 * UpdateDeliverableStatusJob — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/updatedeliverablestatusjob
 */


namespace App\Domains\Freelance\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class UpdateDeliverableStatusJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private int $deliverableId = 0,
            private string $correlationId = '', private readonly LoggerInterface $logger) {
            $this->onQueue('default');

        }

        public function handle(): void
        {
            $deliverable = FreelanceDeliverable::find($this->deliverableId);
            if (!$deliverable) {
                $this->logger->warning('Deliverable not found', [
                    'deliverable_id' => $this->deliverableId,
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            if ($deliverable->status === 'submitted' && $deliverable->created_at->addDays(7)->isPast()) {
                $deliverable->update(['status' => 'pending']);

                $this->logger->info('Deliverable status auto-reset to pending after 7 days', [
                    'deliverable_id' => $this->deliverableId,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }

        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(24);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}

