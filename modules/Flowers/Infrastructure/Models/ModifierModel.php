<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Flowers\Domain\Entities\Modifier as ModifierEntity;

final class ModifierModel extends Model
{
    use SoftDeletes;

    protected $table = 'flowers_modifiers';

    protected $fillable = [
        'venue_id',
        'tenant_id',
        'name',
        'slug',
        'description',
        'type',
        'price',
        'currency',
        'image',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function toDomain(): ModifierEntity
    {
        return new ModifierEntity(
            id: $this->id,
            venueId: $this->venue_id,
            tenantId: $this->tenant_id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            type: $this->type,
            price: (float) $this->price,
            currency: $this->currency,
            image: $this->image,
            isActive: $this->is_active,
            sortOrder: $this->sort_order,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? \Carbon\CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(ModifierEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'venue_id' => $entity->venueId,
            'tenant_id' => $entity->tenantId,
            'name' => $entity->name,
            'slug' => $entity->slug,
            'description' => $entity->description,
            'type' => $entity->type,
            'price' => $entity->price,
            'currency' => $entity->currency,
            'image' => $entity->image,
            'is_active' => $entity->isActive,
            'sort_order' => $entity->sortOrder,
            'metadata' => $entity->metadata,
        ]);
    }
}
