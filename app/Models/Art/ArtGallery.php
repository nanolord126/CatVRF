<?php

declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ArtGallery extends Model
{
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

    /**
     * Boot the model to handle UUID generation and tenant scoping.
     */
    protected static function booted(): void
    {
        self::creating(function (ArtGallery $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
