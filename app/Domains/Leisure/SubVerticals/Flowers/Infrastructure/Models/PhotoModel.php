<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Flowers\Domain\Entities\Photo as PhotoEntity;

final class PhotoModel extends Model
{
    protected $table = 'flowers_photos';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'image_path',
        'thumbnail_path',
        'type',
        'caption',
        'is_visible_to_client',
        'sort_order',
    ];

    protected $casts = [
        'is_visible_to_client' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItemModel::class, 'order_item_id');
    }

    public function scopeVisibleToClient($query)
    {
        return $query->where('is_visible_to_client', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function toDomain(): PhotoEntity
    {
        return new PhotoEntity(
            id: $this->id,
            orderId: $this->order_id,
            orderItemId: $this->order_item_id,
            imagePath: $this->image_path,
            thumbnailPath: $this->thumbnail_path,
            type: $this->type,
            caption: $this->caption,
            isVisibleToClient: $this->is_visible_to_client,
            sortOrder: $this->sort_order,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(PhotoEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'order_id' => $entity->orderId,
            'order_item_id' => $entity->orderItemId,
            'image_path' => $entity->imagePath,
            'thumbnail_path' => $entity->thumbnailPath,
            'type' => $entity->type,
            'caption' => $entity->caption,
            'is_visible_to_client' => $entity->isVisibleToClient,
            'sort_order' => $entity->sortOrder,
        ]);
    }
}
