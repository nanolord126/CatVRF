<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Transfer;

use JsonSerializable;
use InvalidArgumentException;
use DateTimeImmutable;

/**
 * Class TransferWalletResult
 *
 * Represents the final state after successfully transferring funds
 * from one wallet to another. This DTO forms the Application output boundary
 * without exposing domain entities to external systems or HTTP controllers.
 */
final readonly class TransferWalletResult implements JsonSerializable
{
    /**
     * @param string $transactionId The unique ledger identifier for this dual-wallet transfer.
     * @param int $sourceNewBalance The exact new balance on the source wallet after debit.
     * @param int $targetNewBalance The exact new balance on the target wallet after credit.
     * @param string $correlationId The unique trace reference mapping back to external request boundaries.
     * @param DateTimeImmutable $transferredAt Precise moment the transfer succeeded in the underlying store.
     */
    public function __construct(
        public string $transactionId,
        public int $sourceNewBalance,
        public int $targetNewBalance,
        public string $correlationId,
        public DateTimeImmutable $transferredAt
    ) {
        if (trim($this->transactionId) === '') {
            throw new InvalidArgumentException('Transfer operation requires a non-empty transaction identifier.');
        }

        if (trim($this->correlationId) === '') {
            throw new InvalidArgumentException('Transfer operation requires a valid correlation identifier.');
        }

        if ($this->sourceNewBalance < 0) {
            throw new InvalidArgumentException('Source wallet cannot reflect a negative new balance after valid transfer.');
        }

        if ($this->targetNewBalance < 0) {
            throw new InvalidArgumentException('Target wallet cannot reflect a negative new balance after valid transfer.');
        }
    }

    /**
     * Casts DTO payload to strict associative array.
     * Used cleanly in Presenters or CLI formatters.
     * 
     * @return array{transaction_id: string, source_new_balance: int, target_new_balance: int, correlation_id: string, transferred_at: string}
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'source_new_balance' => $this->sourceNewBalance,
            'target_new_balance' => $this->targetNewBalance,
            'correlation_id' => $this->correlationId,
            'transferred_at' => $this->transferredAt->format(DateTimeImmutable::ATOM),
        ];
    }

    /**
     * Contract implementation for JsonSerializable.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
