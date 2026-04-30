<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Entities\Product as ProductEntity;
use Modules\Warehouse\Domain\ValueObjects\ProductId;

final class ProductModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouse_products';

    protected $fillable = [
        'id',
        'sku',
        'name',
        'barcode',
        'description',
        'unit',
        'weight',
        'weight_unit',
        'dimensions',
        'is_hazardous',
        'is_fragile',
        'requires_temperature_control',
        'min_temperature',
        'max_temperature',
        'category',
        'brand',
        'is_active',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_hazardous' => 'boolean',
        'is_fragile' => 'boolean',
        'requires_temperature_control' => 'boolean',
        'weight' => 'decimal:3',
        'min_temperature' => 'decimal:2',
        'max_temperature' => 'decimal:2',
        'dimensions' => 'array',
        'metadata' => 'array',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(BatchModel::class, 'product_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItemModel::class, 'product_sku', 'sku');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySku($query, string $sku)
    {
        return $query->where('sku', $sku);
    }

    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByBrand($query, string $brand)
    {
        return $query->where('brand', $brand);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    public function toDomain(): ProductEntity
    {
        return new ProductEntity(
            id: ProductId::fromString($this->id),
            sku: $this->sku,
            name: $this->name,
            barcode: $this->barcode,
            description: $this->description,
            unit: $this->unit,
            weight: (float) $this->weight,
            weightUnit: $this->weight_unit,
            dimensions: $this->dimensions,
            isHazardous: $this->is_hazardous,
            isFragile: $this->is_fragile,
            requiresTemperatureControl: $this->requires_temperature_control,
            minTemperature: $this->min_temperature ? (float) $this->min_temperature : null,
            maxTemperature: $this->max_temperature ? (float) $this->max_temperature : null,
            category: $this->category,
            brand: $this->brand,
            isActive: $this->is_active,
            createdAt: new \DateTimeImmutable($this->created_at),
            updatedAt: $this->updated_at ? new \DateTimeImmutable($this->updated_at) : null
        );
    }

    public static function fromDomain(ProductEntity $entity): array
    {
        return [
            'id' => $entity->getId()->toString(),
            'sku' => $entity->getSku(),
            'name' => $entity->getName(),
            'barcode' => $entity->getBarcode(),
            'description' => $entity->getDescription(),
            'unit' => $entity->getUnit(),
            'weight' => $entity->getWeight(),
            'weight_unit' => $entity->getWeightUnit(),
            'dimensions' => $entity->getDimensions(),
            'is_hazardous' => $entity->isHazardous(),
            'is_fragile' => $entity->isFragile(),
            'requires_temperature_control' => $entity->requiresTemperatureControl(),
            'min_temperature' => $entity->getMinTemperature(),
            'max_temperature' => $entity->getMaxTemperature(),
            'category' => $entity->getCategory(),
            'brand' => $entity->getBrand(),
            'is_active' => $entity->isActive(),
        ];
    }
}
