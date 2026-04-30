<?php

declare(strict_types=1);

namespace Modules\Restaurant\Enums;

use Illuminate\Support\Str;

/**
 * Order Status — Статус заказа в ресторане
 * CatCRM Standard Color Scheme 2026
 */
enum OrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING_PAYMENT = 'pending_payment';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case IN_KITCHEN = 'in_kitchen';
    case READY = 'ready';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    // Legacy statuses for backward compatibility
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case SERVED = 'served';
    case DELIVERING = 'delivering';
    case DELIVERED = 'delivered';
    case PICKED_UP = 'picked_up';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::PENDING_PAYMENT => 'Ожидает оплаты',
            self::PARTIALLY_PAID => 'Частично оплачен',
            self::PAID => 'Полностью оплачен',
            self::IN_KITCHEN => 'На кухне',
            self::READY => 'Готов к выдаче',
            self::COMPLETED => 'Завершён',
            self::CANCELLED => 'Отменён',
            // Legacy labels
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждён',
            self::PREPARING => 'Готовится',
            self::SERVED => 'Подан',
            self::DELIVERING => 'Доставляется',
            self::DELIVERED => 'Доставлен',
            self::PICKED_UP => 'Забран',
            self::REFUNDED => 'Возвращён',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING_PAYMENT => 'warning',
            self::PARTIALLY_PAID => 'amber',
            self::PAID => 'success',
            self::IN_KITCHEN => 'info',
            self::READY => 'emerald',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            // Legacy colors
            self::PENDING => 'gray',
            self::CONFIRMED => 'blue',
            self::PREPARING => 'yellow',
            self::SERVED => 'emerald',
            self::DELIVERING => 'indigo',
            self::DELIVERED => 'green',
            self::PICKED_UP => 'green',
            self::REFUNDED => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::PENDING_PAYMENT => 'heroicon-o-clock',
            self::PARTIALLY_PAID => 'heroicon-o-credit-card',
            self::PAID => 'heroicon-o-check-circle',
            self::IN_KITCHEN => 'heroicon-o-fire',
            self::READY => 'heroicon-o-hand-raised',
            self::COMPLETED => 'heroicon-o-check-badge',
            self::CANCELLED => 'heroicon-o-x-circle',
            // Legacy icons
            self::PENDING => 'heroicon-o-clock',
            self::CONFIRMED => 'heroicon-o-check-circle',
            self::PREPARING => 'heroicon-o-fire',
            self::SERVED => 'heroicon-o-check',
            self::DELIVERING => 'heroicon-o-truck',
            self::DELIVERED => 'heroicon-o-check-circle',
            self::PICKED_UP => 'heroicon-o-hand-raised',
            self::REFUNDED => 'heroicon-o-arrow-uturn-left',
        };
    }

    public function color(): string
    {
        return $this->filamentColor();
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::REFUNDED,
            self::COMPLETED,
        ], true);
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::CONFIRMED,
            self::PREPARING,
            self::READY,
            self::SERVED,
            self::DELIVERING,
        ], true);
    }

    public function canTransitionTo(OrderStatus $status): bool
    {
        $transitions = [
            self::PENDING => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PREPARING, self::CANCELLED],
            self::PREPARING => [self::READY, self::CANCELLED],
            self::READY => [self::SERVED, self::DELIVERING, self::PICKED_UP],
            self::SERVED => [self::COMPLETED],
            self::DELIVERING => [self::DELIVERED, self::CANCELLED],
            self::DELIVERED => [self::COMPLETED, self::REFUNDED],
            self::PICKED_UP => [self::COMPLETED],
            self::CANCELLED => [],
            self::REFUNDED => [],
            self::COMPLETED => [self::REFUNDED],
        ];

        return in_array($status, $transitions[$this] ?? [], true);
    }

    public function isKitchenStatus(): bool
    {
        return in_array($this, [
            self::PREPARING,
            self::READY,
        ], true);
    }

    public function isDeliveryStatus(): bool
    {
        return in_array($this, [
            self::DELIVERING,
            self::DELIVERED,
        ], true);
    }

    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if (Str::lower($case->label()) === Str::lower($label)) {
                return $case;
            }
        }

        return null;
    }
}
