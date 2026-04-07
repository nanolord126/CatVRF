<?php

declare(strict_types=1);

namespace App\Domains\Inventory\DTOs;

/**
 * DTO для поиска/фильтрации остатков.
 */
final readonly class SearchInventoryDto
{
    public function __construct(
        public int     $tenantId,
        public ?int    $warehouseId = null,
        public ?int    $productId = null,
        public ?bool   $inStockOnly = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortDirection = 'desc',
        public int     $perPage = 20,
        public int     $page = 1,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId:      (int) ($data['tenant_id'] ?? 0),
            warehouseId:   isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            productId:     isset($data['product_id']) ? (int) $data['product_id'] : null,
            inStockOnly:   isset($data['in_stock_only']) ? (bool) $data['in_stock_only'] : null,
            sortBy:        $data['sort_by'] ?? 'created_at',
            sortDirection: $data['sort_direction'] ?? 'desc',
            perPage:       (int) ($data['per_page'] ?? 20),
            page:          (int) ($data['page'] ?? 1),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tenant_id'      => $this->tenantId,
            'warehouse_id'   => $this->warehouseId,
            'product_id'     => $this->productId,
            'in_stock_only'  => $this->inStockOnly,
            'sort_by'        => $this->sortBy,
            'sort_direction'  => $this->sortDirection,
            'per_page'       => $this->perPage,
            'page'           => $this->page,
        ];
    }
}
