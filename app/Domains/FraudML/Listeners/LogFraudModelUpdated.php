<?php declare(strict_types=1);

/**
 * LogFraudModelUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logfraudmodelupdated
 */


namespace App\Domains\FraudML\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\FraudML\Events\FraudModelUpdated;
/**
 * Class LogFraudModelUpdated
 *
 * Part of the FraudML vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\FraudML\Listeners
 */
final class LogFraudModelUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(FraudModelUpdated $event): void
    {
        $this->logger->info('FraudModel updated', [
            'model_id' => $event->fraudModel->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->fraudModel->tenant_id ?? null,
        ]);
    }
}
