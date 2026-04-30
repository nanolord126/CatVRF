<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Flowers\Domain\Entities\Flower as FlowerEntity;
use Modules\Flowers\Domain\Enums\FreshnessStatus;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class FlowerModel extends Model
{
    use SoftDeletes;
    use HasMediaTrait;

    protected $table = 'flowers_flowers';

    protected $fillable = [
        'venue_id',
        'tenant_id',
        'name',
        'slug',
        'category',
        'color',
        'variety',
        'description',
        'supplier',
        'supplier_code',
        'stock_quantity',
        'reserved_quantity',
        'minimum_stock',
        'cost_price',
        'selling_price',
        'unit',
        'units_per_bunch',
        'expiry_date',
        'received_date',
        'freshness_status',
        'stem_length_cm',
        'storage_conditions',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'minimum_stock' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'units_per_bunch' => 'integer',
        'expiry_date' => 'date',
        'received_date' => 'date',
        'freshness_status' => FreshnessStatus::class,
        'stem_length_cm' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ProductModel::class, 'flowers_product_flowers', 'flower_id', 'product_id')
            ->withPivot(['quantity', 'unit', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByColor($query, string $color)
    {
        return $query->where('color', $color);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('(stock_quantity - reserved_quantity) <= minimum_stock');
    }

    public function scopeExpiringSoon($query, int $days = 3)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeFresh($query)
    {
        return $query->where('freshness_status', FreshnessStatus::FRESH);
    }

    public function toDomain(): FlowerEntity
    {
        return new FlowerEntity(
            id: $this->id,
            venueId: $this->venue_id,
            tenantId: $this->tenant_id,
            name: $this->name,
            slug: $this->slug,
            category: $this->category,
            color: $this->color,
            variety: $this->variety,
            description: $this->description,
            supplier: $this->supplier,
            supplierCode: $this->supplier_code,
            stockQuantity: $this->stock_quantity,
            reservedQuantity: $this->reserved_quantity,
            minimumStock: $this->minimum_stock,
            costPrice: (float) $this->cost_price,
            sellingPrice: (float) $this->selling_price,
            unit: $this->unit,
            unitsPerBunch: $this->units_per_bunch,
            expiryDate: \Carbon\CarbonImmutable::parse($this->expiry_date),
            receivedDate: \Carbon\CarbonImmutable::parse($this->received_date),
            freshnessStatus: $this->freshness_status,
            stemLengthCm: $this->stem_length_cm,
            storageConditions: $this->storage_conditions,
            isActive: $this->is_active,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? \Carbon\CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(FlowerEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'venue_id' => $entity->venueId,
            'tenant_id' => $entity->tenantId,
            'name' => $entity->name,
            'slug' => $entity->slug,
            'category' => $entity->category,
            'color' => $entity->color,
            'variety' => $entity->variety,
            'description' => $entity->description,
            'supplier' => $entity->supplier,
            'supplier_code' => $entity->supplierCode,
            'stock_quantity' => $entity->stockQuantity,
            'reserved_quantity' => $entity->reservedQuantity,
            'minimum_stock' => $entity->minimumStock,
            'cost_price' => $entity->costPrice,
            'selling_price' => $entity->sellingPrice,
            'unit' => $entity->unit,
            'units_per_bunch' => $entity->unitsPerBunch,
            'expiry_date' => $entity->expiryDate->format('Y-m-d'),
            'received_date' => $entity->receivedDate->format('Y-m-d'),
            'freshness_status' => $entity->freshnessStatus,
            'stem_length_cm' => $entity->stemLengthCm,
            'storage_conditions' => $entity->storageConditions,
            'is_active' => $entity->isActive,
            'metadata' => $entity->metadata,
        ]);
    }
}
