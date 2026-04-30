<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Media;

trait HasEcosystemMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function getPrimaryImageUrl(): ?string
    {
        $media = $this->media()->where('is_primary', true)->first();

        return $media?->url;
    }

    public function getMediaUrls(): array
    {
        return $this->media()->pluck('url')->toArray();
    }
}
