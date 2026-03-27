<?php

declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PartyTheme Model.
 * Represents seasonal or specific collection (e.g., Halloween, Wedding, Birthday).
 * 
 * @property string $uuid
 * @property int $tenant_id
 * @property int $party_store_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property array $color_palette
 * @property array $metadata
 * @property bool $is_seasonal
 * @property string $season_start
 * @property string $season_end
 * @property bool $is_active
 */
final class PartyTheme extends Model
{
    use SoftDeletes;

    protected $table = 'party_themes';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'party_store_id',
        'name',
        'slug',
        'description',
        'color_palette',
        'metadata',
        'is_seasonal',
        'season_start',
        'season_end',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'color_palette' => 'json',
        'metadata' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
        'is_seasonal' => 'boolean',
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
     * Relationship: Theme products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(PartyProduct::class, 'party_theme_id');
    }

    /**
     * Relationship: Owning store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(PartyStore::class, 'party_store_id');
    }

    /**
     * Checker: If the current date is within the season range.
     */
    public function isCurrentSeason(): bool
    {
        if (!$this->is_seasonal) {
            return true;
        }

        if (!$this->season_start || !$this->season_end) {
            return false;
        }

        $now = now();
        return $now->isBetween($this->season_start, $this->season_end);
    }
}
