<?php

declare(strict_types=1);

namespace App\Domains\Payment\ValueObjects;

use InvalidArgumentException;

/**
 * Payment Status Value Object - immutable payment status with state transitions.
 *
 * Follows state machine pattern for valid status transitions.
 */
final readonly class PaymentStatusVO
{
    public const string PENDING = 'pending';
    public const string AUTHORIZED = 'authorized';
    public const string CAPTURED = 'captured';
    public const string PARTIALLY_CAPTURED = 'partially_captured';
    public const string REFUNDED = 'refunded';
    public const string PARTIALLY_REFUNDED = 'partially_refunded';
    public const string FAILED = 'failed';
    public const string CANCELLED = 'cancelled';
    public const string EXPIRED = 'expired';

    private const array VALID_TRANSITIONS = [
        self::PENDING => [self::AUTHORIZED, self::FAILED, self::CANCELLED, self::EXPIRED],
        self::AUTHORIZED => [self::CAPTURED, self::PARTIALLY_CAPTURED, self::CANCELLED, self::FAILED],
        self::CAPTURED => [self::REFUNDED, self::PARTIALLY_REFUNDED],
        self::PARTIALLY_CAPTURED => [self::CAPTURED, self::REFUNDED, self::PARTIALLY_REFUNDED, self::CANCELLED],
        self::PARTIALLY_REFUNDED => [self::REFUNDED],
        self::REFUNDED => [],
        self::FAILED => [],
        self::CANCELLED => [],
        self::EXPIRED => [],
    ];

    private const array FINAL_STATES = [
        self::REFUNDED,
        self::FAILED,
        self::CANCELLED,
        self::EXPIRED,
    ];

    public function __construct(
        public string $status,
    ) {
        $this->validateStatus($status);
    }

    /**
     * Create from string.
     */
    public static function fromString(string $status): self
    {
        return new self($status);
    }

    /**
     * Check if status is terminal (no further transitions possible).
     */
    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATES, true);
    }

    /**
     * Check if payment is successful (captured or partially captured).
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::CAPTURED || $this->status === self::PARTIALLY_CAPTURED;
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::FAILED || $this->status === self::CANCELLED || $this->status === self::EXPIRED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    /**
     * Check if payment is authorized (hold).
     */
    public function isAuthorized(): bool
    {
        return $this->status === self::AUTHORIZED;
    }

    /**
     * Check if payment is captured.
     */
    public function isCaptured(): bool
    {
        return $this->status === self::CAPTURED;
    }

    /**
     * Check if payment can be refunded.
     */
    public function canRefund(): bool
    {
        return $this->status === self::CAPTURED || $this->status === self::PARTIALLY_CAPTURED || $this->status === self::PARTIALLY_REFUNDED;
    }

    /**
     * Check if payment can be captured.
     */
    public function canCapture(): bool
    {
        return $this->status === self::AUTHORIZED || $this->status === self::PARTIALLY_CAPTURED;
    }

    /**
     * Check if payment can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->status === self::PENDING || $this->status === self::AUTHORIZED || $this->status === self::PARTIALLY_CAPTURED;
    }

    /**
     * Check if transition to new status is valid.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus->status, self::VALID_TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Validate and transition to new status.
     */
    public function transitionTo(self $newStatus): self
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                sprintf('Cannot transition from %s to %s', $this->status, $newStatus->status)
            );
        }

        return $newStatus;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this->status) {
            self::PENDING => 'Ожидает оплаты',
            self::AUTHORIZED => 'Авторизован (холд)',
            self::CAPTURED => 'Оплачен',
            self::PARTIALLY_CAPTURED => 'Частично оплачен',
            self::REFUNDED => 'Возвращен',
            self::PARTIALLY_REFUNDED => 'Частично возвращен',
            self::FAILED => 'Ошибка',
            self::CANCELLED => 'Отменен',
            self::EXPIRED => 'Истек',
            default => 'Неизвестно',
        };
    }

    private function validateStatus(string $status): void
    {
        $validStatuses = array_keys(self::VALID_TRANSITIONS);
        if (! in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid payment status: %s. Valid statuses: %s', $status, implode(', ', $validStatuses))
            );
        }
    }
}
