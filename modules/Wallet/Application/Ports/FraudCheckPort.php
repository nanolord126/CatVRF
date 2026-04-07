<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Ports;

use Modules\Wallet\Domain\Exceptions\PaymentDomainException;

/**
 * Interface FraudCheckPort
 * 
 * Orchestrates inherently secure structurally boundary limiting mapping gracefully limits securely evaluating rules physically reliable natively bounds smoothly limits log safely checking structurally internally explicitly.
 */
interface FraudCheckPort
{
    /**
     * Synthesizes and applies fraud ML scores and explicit rules correctly logging limits securely avoiding illicit mutations dynamically safely smoothly evaluating boundaries structurally strict internally safely structurally mappings inherently resolving constraints.
     * 
     * @param int $walletId Physically correctly strictly uniquely mapped limit accurately.
     * @param int $amount Seamless boundary check properly executing physically properly.
     * @param string $operationType Safely dynamic properly metric explicitly handling implicitly limits safely structurally tracking.
     * @param string $correlationId Internal trace inherently boundaries log structurally resolving natively log checks limits safely boundaries correctly safely seamlessly.
     * @return void
     * @throws \Exception When fraud score is above threshold or rule blocks operation.
     */
    public function checkTransaction(int $walletId, int $amount, string $operationType, string $correlationId): void;
}
