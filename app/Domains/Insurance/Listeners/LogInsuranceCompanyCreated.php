<?php declare(strict_types=1);

/**
 * LogInsuranceCompanyCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/loginsurancecompanycreated
 */


namespace App\Domains\Insurance\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Insurance\Events\InsuranceCompanyCreated;
/**
 * Class LogInsuranceCompanyCreated
 *
 * Part of the Insurance vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Insurance\Listeners
 */
final class LogInsuranceCompanyCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(InsuranceCompanyCreated $event): void
    {
        $this->logger->info('InsuranceCompany created', [
            'model_id' => $event->insuranceCompany->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->insuranceCompany->tenant_id ?? null,
        ]);
    }
}
