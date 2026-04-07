<?php

declare(strict_types=1);

namespace Modules\Payments\Adapters;

use App\Services\FraudControlService;
use Modules\Payments\Ports\FraudCheckPort;

/**
 * Adapter: FraudCheck → FraudControlService (app-level).
 */
final readonly class FraudCheckAdapter implements FraudCheckPort
{
    public function __construct(
        private FraudControlService $fraudControl,
    ) {}

    public function check(
        int    $userId,
        string $operationType,
        int    $amount,
        array  $context = [],
    ): void {
        $result = $this->fraudControl->check(
            $userId,
            $operationType,
            $amount,
            $context['ip_address']         ?? null,
            $context['device_fingerprint'] ?? null,
            $context['correlation_id']     ?? '',
        );

        if (isset($result['decision']) && $result['decision'] === 'block') {
            throw new \DomainException(
                $result['reason'] ?? 'Операция заблокирована: подозрение на мошенничество'
            );
        }
    }
}
