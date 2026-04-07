<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Transfer;

use InvalidArgumentException;

/**
 * Class TransferWalletCommand
 *
 * Represents the intent to transfer funds between two distinct wallets.
 * This class validates the incoming request payload at the application boundary,
 * ensuring no structural inconsistencies are passed to the exact domain execution logic.
 */
final readonly class TransferWalletCommand
{
    /**
     * @param string $sourceWalletId The unique ID of the wallet from which funds will be deducted.
     * @param string $targetWalletId The unique ID of the wallet to which funds will be added.
     * @param int $amount The amount to transfer in the smallest currency unit (e.g., kopecks).
     * @param string $tenantId The ID of the tenant to strictly scope this transfer action.
     * @param string $correlationId Distributed tracing ID for the request chain.
     * @param string $reason Business reason for the transfer (e.g., 'Payment for services').
     */
    public function __construct(
        public string $sourceWalletId,
        public string $targetWalletId,
        public int $amount,
        public string $tenantId,
        public string $correlationId,
        public string $reason
    ) {
        if (trim($this->sourceWalletId) === '') {
            throw new InvalidArgumentException('Source wallet ID cannot be empty string.');
        }

        if (trim($this->targetWalletId) === '') {
            throw new InvalidArgumentException('Target wallet ID cannot be empty string.');
        }

        if ($this->sourceWalletId === $this->targetWalletId) {
            throw new InvalidArgumentException('Source and Target wallet IDs cannot be identical.');
        }

        if ($this->amount <= 0) {
            throw new InvalidArgumentException('Transfer amount must be strictly greater than zero.');
        }

        if (trim($this->tenantId) === '') {
            throw new InvalidArgumentException('Tenant ID cannot be empty. All operations are strictly tenant-scoped.');
        }

        if (trim($this->correlationId) === '') {
            throw new InvalidArgumentException('Correlation ID must be provided for tracking and logging.');
        }

        if (trim($this->reason) === '') {
            throw new InvalidArgumentException('Transfer operation strictly requires a valid reason string.');
        }
    }
}
