<?php

declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class PartyCategory extends Model
{
    protected $table = 'party_categories';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'party_store_id',
        'name',
        'slug',
        'description',
        'metadata',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'metadata' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Store owning this category.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(PartyStore::class, 'party_store_id');
    }

    /**
     * Relationship: Category products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(PartyProduct::class, 'party_category_id');
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
