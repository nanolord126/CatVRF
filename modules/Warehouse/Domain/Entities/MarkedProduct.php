<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

/**
 * Marked Product Entity for Честный ЗНАК integration
 * 
 * Сущность для товаров с кодом маркировки DataMatrix
 */
final readonly class MarkedProduct
{
    public function __construct(
        private string $cis, // Код маркировки (DataMatrix)
        private int $quantity,
        private ?float $price = null,
        private ?string $gtin = null,
        private ?string $serial = null,
        private ?string $expiry = null,
        private ?string $batch = null
    ) {
        // Валидация формата кода маркировки
        $this->validateCisFormat();
    }

    public static function create(
        string $cis,
        int $quantity,
        ?float $price = null
    ): self {
        return new self(
            cis: $cis,
            quantity: $quantity,
            price: $price
        );
    }

    public static function fromParsedData(
        string $cis,
        int $quantity,
        ?float $price,
        ?string $gtin,
        ?string $serial,
        ?string $expiry,
        ?string $batch
    ): self {
        return new self(
            cis: $cis,
            quantity: $quantity,
            price: $price,
            gtin: $gtin,
            serial: $serial,
            expiry: $expiry,
            batch: $batch
        );
    }

    public function getCis(): string
    {
        return $this->cis;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getGtin(): ?string
    {
        return $this->gtin;
    }

    public function getSerial(): ?string
    {
        return $this->serial;
    }

    public function getExpiry(): ?string
    {
        return $this->expiry;
    }

    public function getBatch(): ?string
    {
        return $this->batch;
    }

    public function toArray(): array
    {
        return [
            'cis' => $this->cis,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'gtin' => $this->gtin,
            'serial' => $this->serial,
            'expiry' => $this->expiry,
            'batch' => $this->batch,
        ];
    }

    private function validateCisFormat(): void
    {
        // DataMatrix формат: (01)GTIN(21)SERIAL(17)EXPIRY(10)BATCH
        $pattern = '/^\(01\d{14}\)\(21\d{7}\)\(17\d{6}\)\(10.{6}\)$/';
        
        if (!preg_match($pattern, $this->cis)) {
            throw new \InvalidArgumentException(
                "Invalid CIS format: {$this->cis}. Expected DataMatrix format."
            );
        }
    }

    /**
     * Парсинг кода маркировки для извлечения компонентов
     */
    public static function parseCis(string $cis): array
    {
        $gtin = null;
        $serial = null;
        $expiry = null;
        $batch = null;

        if (preg_match('/\(01(\d{14})\)/', $cis, $matches)) {
            $gtin = $matches[1];
        }

        if (preg_match('/\(21(\d{7})\)/', $cis, $matches)) {
            $serial = $matches[1];
        }

        if (preg_match('/\(17(\d{6})\)/', $cis, $matches)) {
            $expiry = $matches[1]; // YYMMDD
        }

        if (preg_match('/\(10(.{6})\)/', $cis, $matches)) {
            $batch = $matches[1];
        }

        return [
            'cis' => $cis,
            'gtin' => $gtin,
            'serial' => $serial,
            'expiry' => $expiry,
            'batch' => $batch,
        ];
    }
}
