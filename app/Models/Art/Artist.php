<?php declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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

        protected static function booted(): void
        {
            static::creating(function (Artist $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

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
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }
}
