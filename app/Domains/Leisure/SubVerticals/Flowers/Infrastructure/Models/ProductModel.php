<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Flowers\Domain\Entities\Product as ProductEntity;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class ProductModel extends Model
{
    use SoftDeletes;
    use HasMediaTrait;

    protected $table = 'flowers_products';

    protected $fillable = [
        'venue_id',
        'tenant_id',
        'name',
        'slug',
        'description',
        'category',
        'size',
        'base_price',
        'discount_price',
        'currency',
        'main_image',
        'gallery_images',
        'composition_notes',
        'preparation_time_minutes',
        'is_seasonal',
        'is_featured',
        'is_active',
        'sort_order',
        'seo_data',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'gallery_images' => 'array',
        'preparation_time_minutes' => 'integer',
        'is_seasonal' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'seo_data' => 'array',
        'metadata' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function flowers(): BelongsToMany
    {
        return $this->belongsToMany(FlowerModel::class, 'flowers_product_flowers', 'product_id', 'flower_id')
            ->withPivot(['quantity', 'unit', 'notes'])
            ->withTimestamps();
    }

    public function orderItems(): BelongsToMany
    {
        return $this->hasMany(OrderItemModel::class, 'product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySize($query, string $size)
    {
        return $query->where('size', $size);
    }

    public function scopeOnDiscount($query)
    {
        return $query->whereNotNull('discount_price')
            ->where('discount_price', '<', \DB::raw('base_price'));
    }

    public function toDomain(): ProductEntity
    {
        return new ProductEntity(
            id: $this->id,
            venueId: $this->venue_id,
            tenantId: $this->tenant_id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            category: $this->category,
            size: $this->size,
            basePrice: (float) $this->base_price,
            discountPrice: $this->discount_price ? (float) $this->discount_price : null,
            currency: $this->currency,
            mainImage: $this->main_image,
            galleryImages: $this->gallery_images,
            compositionNotes: $this->composition_notes,
            preparationTimeMinutes: $this->preparation_time_minutes,
            isSeasonal: $this->is_seasonal,
            isFeatured: $this->is_featured,
            isActive: $this->is_active,
            sortOrder: $this->sort_order,
            seoData: $this->seo_data,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? \Carbon\CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(ProductEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'venue_id' => $entity->venueId,
            'tenant_id' => $entity->tenantId,
            'name' => $entity->name,
            'slug' => $entity->slug,
            'description' => $entity->description,
            'category' => $entity->category,
            'size' => $entity->size,
            'base_price' => $entity->basePrice,
            'discount_price' => $entity->discountPrice,
            'currency' => $entity->currency,
            'main_image' => $entity->mainImage,
            'gallery_images' => $entity->galleryImages,
            'composition_notes' => $entity->compositionNotes,
            'preparation_time_minutes' => $entity->preparationTimeMinutes,
            'is_seasonal' => $entity->isSeasonal,
            'is_featured' => $entity->isFeatured,
            'is_active' => $entity->isActive,
            'sort_order' => $entity->sortOrder,
            'seo_data' => $entity->seoData,
            'metadata' => $entity->metadata,
        ]);
    }
}
