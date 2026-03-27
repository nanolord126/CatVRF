<?php

declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PartyGiftSet Model.
 * Represents curated bundles of products (Balloons + Decor + etc).
 * 
 * @property string $uuid
 * @property int $tenant_id
 * @property int $party_store_id
 * @property string $name
 * @property string $description
 * @property int $price_cents
 * @property array $items_json
 * @property array $metadata
 * @property bool $is_active
 */
final class PartyGiftSet extends Model
{
    use SoftDeletes;

    protected $table = 'party_gift_sets';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'party_store_id',
        'name',
        'description',
        'price_cents',
        'items_json',
        'metadata',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'items_json' => 'json',
        'metadata' => 'json',
        'tags' => 'json',
        'price_cents' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot logic for automatic UUID and tenant scoping.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Relationship: Owning store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(PartyStore::class, 'party_store_id');
    }
}
