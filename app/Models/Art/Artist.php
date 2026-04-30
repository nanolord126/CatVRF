<?php

declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\User;

final class Artist extends Model
{
    protected $table = 'artists';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'full_name',
        'pseudonym',
        'biography',
        'specialization',
        'experience_years',
        'rating',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'specialization' => 'json',
        'tags' => 'json',
        'rating' => 'float',
    ];

    /**
     * Get artworks created by this artist.
     */
    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class, 'artist_id');
    }

    /**
     * Associated user account if any.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted(): void
    {
        self::creating(function (Artist $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
