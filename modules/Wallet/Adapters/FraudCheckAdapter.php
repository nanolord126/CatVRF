<?php

declare(strict_types=1);

namespace Modules\Wallet\Adapters;

use App\Services\FraudControlService;
use Modules\Wallet\Ports\FraudCheckPort;

/**
 * Адаптер: реализует FraudCheckPort через глобальный FraudControlService.
 */
final readonly class FraudCheckAdapter implements FraudCheckPort
{
    public function __construct(
        private FraudControlService $fraud,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function check(
        int    $userId,
        string $operationType,
        int    $amount,
        array  $context = [],
    ): void {
        $result = $this->fraud->check(
            userId:            $userId,
            operationType:     $operationType,
            amount:            $amount,
            ipAddress:         $context['ip_address'] ?? null,
            deviceFingerprint: $context['device_fingerprint'] ?? null,
            correlationId:     $context['correlation_id'] ?? '',
        );

        if (isset($result['decision']) && $result['decision'] === 'block') {
            throw new \DomainException(
                $result['reason'] ?? 'Операция заблокирована: подозрение на мошенничество'
            );
        }
    }
}
