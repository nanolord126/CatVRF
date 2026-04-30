<?php

declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class PartyGiftSet extends Model
{
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
     * Relationship: Owning store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(PartyStore::class, 'party_store_id');
    }

    /**
     * Boot logic for automatic UUID and tenant scoping.
     */
    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });

        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
