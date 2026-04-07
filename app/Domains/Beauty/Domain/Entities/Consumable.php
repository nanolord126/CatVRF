<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Entities;

use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Shared\Domain\Entities\Entity;
use InvalidArgumentException;

/**
 * Расходник (consumable) — материалы, списываемые при завершении услуги.
 * Примеры: перчатки, краска, полотенца, лак и т.д.
 */
final class Consumable extends Entity
{
    public function __construct(
        private string       $id,
        private int          $tenantId,
        private ServiceId    $serviceId,
        private string                $name,
        private string                $unit,
        private int                   $currentStock,
        private int                   $holdStock,
        private int                   $minStockThreshold,
        private float                 $quantityPerService,
        private string                $correlationId,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable    $updatedAt,
    ) {
        if ($currentStock < 0) {
            throw new InvalidArgumentException('Consumable stock cannot be negative.');
        }
        if ($quantityPerService <= 0) {
            throw new InvalidArgumentException('Quantity per service must be positive.');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getServiceId(): ServiceId
    {
        return $this->serviceId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getCurrentStock(): int
    {
        return $this->currentStock;
    }

    public function getHoldStock(): int
    {
        return $this->holdStock;
    }

    public function getMinStockThreshold(): int
    {
        return $this->minStockThreshold;
    }

    public function getQuantityPerService(): float
    {
        return $this->quantityPerService;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Зарезервировать расходник при создании записи.
     */
    public function hold(float $quantity): void
    {
        $needed = (int) ceil($quantity);

        if (($this->currentStock - $this->holdStock) < $needed) {
            throw new \DomainException(
                "Insufficient consumable stock for '{$this->name}': available " .
                ($this->currentStock - $this->holdStock) . ", needed {$needed}."
            );
        }

        $this->holdStock += $needed;
        $this->touch();
    }

    /**
     * Снять резерв при отмене записи.
     */
    public function releaseHold(float $quantity): void
    {
        $amount         = (int) ceil($quantity);
        $this->holdStock = max(0, $this->holdStock - $amount);
        $this->touch();
    }

    /**
     * Списать расходник при завершении услуги.
     * Снимает hold и уменьшает currentStock.
     */
    public function deduct(float $quantity): void
    {
        $amount = (int) ceil($quantity);

        if ($this->currentStock < $amount) {
            throw new \DomainException(
                "Cannot deduct {$amount} of '{$this->name}': only {$this->currentStock} in stock."
            );
        }

        $this->currentStock -= $amount;
        $this->holdStock     = max(0, $this->holdStock - $amount);
        $this->touch();
    }

    /**
     * Пополнить запас расходника.
     */
    public function restock(int $quantity, string $reason): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Restock quantity must be positive.');
        }

        $this->currentStock += $quantity;
        $this->touch();
    }

    /**
     * Проверить, ниже ли запас порогового значения.
     */
    public function isBelowThreshold(): bool
    {
        return $this->currentStock <= $this->minStockThreshold;
    }

    /**
     * Доступный (не под холдом) остаток.
     */
    public function availableStock(): int
    {
        return max(0, $this->currentStock - $this->holdStock);
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'tenant_id'           => $this->tenantId,
            'service_id'          => $this->serviceId->getValue(),
            'name'                => $this->name,
            'unit'                => $this->unit,
            'current_stock'       => $this->currentStock,
            'hold_stock'          => $this->holdStock,
            'available_stock'     => $this->availableStock(),
            'min_stock_threshold' => $this->minStockThreshold,
            'quantity_per_service' => $this->quantityPerService,
            'is_below_threshold'  => $this->isBelowThreshold(),
        ];
    }
}
