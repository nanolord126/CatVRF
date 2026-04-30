<?php

declare(strict_types=1);

namespace Modules\Marketplace\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Domain\Entities\ProductListing;

/**
 * Resource для витринной позиции маркетплейса
 */
final class ProductListingResource extends JsonResource
{
    /** @var ProductListing */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid->toString(),
            'source' => $this->resource->source->value,
            'type' => $this->resource->type->value,
            'status' => $this->resource->status->value,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'price' => [
                'amount' => $this->resource->price->amount,
                'currency' => $this->resource->price->currency,
                'formatted' => $this->resource->price->format(),
            ],
            'rating' => $this->resource->rating?->toArray(),
            'review_count' => $this->resource->reviewCount,
            'metrics' => [
                'view_count' => $this->resource->viewCount,
                'order_count' => $this->resource->orderCount,
                'conversion_rate' => $this->resource->conversionRate,
                'popularity_score' => $this->resource->popularityScore,
                'ranking_score' => $this->resource->rankingScore,
            ],
            'categories' => $this->resource->categories,
            'tags' => $this->resource->tags,
            'images' => $this->resource->images,
            'thumbnail' => $this->resource->thumbnail,
            'attributes' => $this->resource->attributes,
            'inventory' => [
                'stock_quantity' => $this->resource->stockQuantity,
                'in_stock' => $this->resource->inStock,
            ],
            'flags' => [
                'is_featured' => $this->resource->isFeatured,
                'is_promoted' => $this->resource->isPromoted,
                'is_available' => $this->resource->isAvailable(),
            ],
            'timestamps' => [
                'created_at' => $this->resource->createdAt->format('Y-m-d H:i:s'),
                'updated_at' => $this->resource->updatedAt->format('Y-m-d H:i:s'),
                'published_at' => $this->resource->publishedAt?->format('Y-m-d H:i:s'),
                'expires_at' => $this->resource->expiresAt?->format('Y-m-d H:i:s'),
            ],
            'links' => [
                'self' => route('marketplace.show', ['uuid' => $this->resource->uuid->toString()]),
                'similar' => route('marketplace.similar', ['uuid' => $this->resource->uuid->toString()]),
            ],
        ];
    }

    public static function collection($resource)
    {
        return parent::collection($resource);
    }
}
