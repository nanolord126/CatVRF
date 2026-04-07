<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Debit;

use JsonSerializable;
use InvalidArgumentException;
use DateTimeImmutable;

/**
 * Class DebitWalletResult
 *
 * Represents the successful outcome of a wallet debit operation.
 * This DTO is strictly immutable and serves as the strict contract between
 * the Application layer (UseCase) and the Presentation layer (Controller/CLI/Job).
 *
 * It ensures that only validated, consistent data leaves the Application boundary,
 * maintaining strict isolation from Domain aggregates. We do not leak the Wallet aggregate.
 */
final readonly class DebitWalletResult implements JsonSerializable
{
    /**
     * @param string $transactionId The unique identifier of the internal ledger transaction.
     * @param int $newBalance The new balance of the wallet in the smallest currency unit (e.g., kopecks).
     * @param string $correlationId The distributed tracing ID tracking this exact flow.
     * @param string $walletId The identity of the wallet that was debited.
     * @param DateTimeImmutable $debitedAt Exact timestamp of the debit application.
     */
    public function __construct(
        public string $transactionId,
        public int $newBalance,
        public string $correlationId,
        public string $walletId,
        public DateTimeImmutable $debitedAt
    ) {
        if (trim($this->transactionId) === '') {
            throw new InvalidArgumentException('DebitWalletResult requires a valid transaction ID.');
        }

        if (trim($this->correlationId) === '') {
            throw new InvalidArgumentException('DebitWalletResult requires a valid correlation ID.');
        }

        if (trim($this->walletId) === '') {
            throw new InvalidArgumentException('DebitWalletResult requires a valid wallet ID.');
        }

        if ($this->newBalance < 0) {
            // Under normal circumstances, a wallet balance shouldn't drop below zero after standard debit.
            // If credit limits / overdrafts are supported later, this assertion might be moved to domain.
            throw new InvalidArgumentException('DebitWalletResult cannot reflect a negative new balance.');
        }
    }

    /**
     * Retrieve the result as a standardized primitive array.
     * Useful for formatting strictly into JSON Responses or passing into legacy event systems.
     * 
     * @return array{transaction_id: string, new_balance: int, correlation_id: string, wallet_id: string, debited_at: string}
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'new_balance' => $this->newBalance,
            'correlation_id' => $this->correlationId,
            'wallet_id' => $this->walletId,
            'debited_at' => $this->debitedAt->format(DateTimeImmutable::ATOM),
        ];
    }

    /**
     * Implementation of JsonSerializable to allow direct json_encode() usage on this result object.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
