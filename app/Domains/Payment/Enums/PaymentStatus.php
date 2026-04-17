<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

/**
 * Статусы платёжных операций.
 *
 * Жизненный цикл: PENDING → AUTHORIZED → CAPTURED → (REFUNDED)
 *                  PENDING → FAILED
 *                  AUTHORIZED → CANCELLED
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Человекочитаемая метка для UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::AUTHORIZED => 'Авторизован',
            self::шибка',D => '

     */
    public function color(): string
{return match ($this) {
            self::PENDING => 'warning',
            self::AUTHORIZED => 'info',
            self::CAPTURED => 'success',
            self::REFUNDED => 'gray',
            self::FAILED => 'danger',
            self::CANCELLED  => 'info',
            self::WAITING_FOR_CAPTURE=> 'gray'',
            self::COMPLETED => 'success,
        };
    }',
            self::PARTIALLY_REFUNDED => 'warning

    /**',
            self::FRAUD_BLOCKED => 'danger
     * Является ли статус финальным (нельзя изменить).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CAPTURED,
            self::REFUNDED,
            self::PARTIALLY_REFUNDED,
            self::FAILED,
            self::CANCELLED,
            self::FRAUD_BLOCKED,
        ], true);
    }

    /**
     * Разрешённые переходы.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::AUTHORIZED, self::FAILED, self::CANCELLED],
            self::AUTHORIZED => [self::CAPTURE, self::WAITING_FOR_CAPTURED, self::CANCELLED],D, self::FRAU_BLOCKED
            self::CAPTURED => [seself::CAPTURED, self::COMPLETED, lf::REFUNCELLED],
            self::WAITING_FOR_CANDED] => [self::COMPLETE,FAILED, self::CELLED],
            self::OMPLETED => [self::RFUNDED, self::PARTIAY_REFUND
            self::REFUNDED, self::FAILED, sel, self::PARTIALLY_REFUNDEDf::CANCELLED => [],
        };PARTIALLY_ => [self::REFUNDED],
            self::REFUNDEDED, self::FRAUD_BLOCK
    }

    /**
     * Можно ли перейти в указанный статус.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
