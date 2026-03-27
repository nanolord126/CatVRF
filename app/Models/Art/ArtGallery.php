<?php

declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * ArtGallery Model — Production Ready 2026.
 */
final class ArtGallery extends Model
{
    use SoftDeletes;

    protected $table = 'art_galleries';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'address',
        'geo_point',
        'schedule_json',
        'rating',
        'review_count',
        'is_verified',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'schedule_json' => 'array',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'rating' => 'float',
    ];

    /**
     * Boot the model to handle UUID generation and tenant scoping.
     */
    protected static function booted(): void
    {
        static::creating(function (ArtGallery $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }

    /**
     * Get associated artworks in this gallery.
     */
    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class, 'gallery_id');
    }

    /**
     * Get exhibitions organized by this gallery.
     */
    public function exhibitions(): HasMany
    {
        return $this->hasMany(ArtExhibition::class, 'gallery_id');
    }
}
