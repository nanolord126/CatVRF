<?php declare(strict_types=1);

/**
 * LogAIModelCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logaimodelcreated
 */


namespace App\Domains\AI\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\AI\Events\AIModelCreated;
/**
 * Class LogAIModelCreated
 *
 * Part of the AI vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\AI\Listeners
 */
final class LogAIModelCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(AIModelCreated $event): void
    {
        $this->logger->info('AIModel created', [
            'model_id' => $event->aIModel->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->aIModel->tenant_id ?? null,
        ]);
    }
}
