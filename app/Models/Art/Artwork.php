<?php declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class Artwork extends Model
{
    use HasFactory;
    use SoftDeletes;

        protected $table = 'artworks';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'gallery_id',
            'artist_id',
            'title',
            'description',
            'type',
            'dimensions',
            'price_cents',
            'stock_quantity',
            'is_original',
            'has_certificate',
            'style',
            'material_main',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'dimensions' => 'array',
            'tags' => 'json',
            'is_original' => 'boolean',
            'has_certificate' => 'boolean',
            'price_cents' => 'integer',
            'stock_quantity' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (Artwork $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

        /**
         * Associated gallery where artwork is listed.
         */
        public function gallery(): BelongsTo
        {
            return $this->belongsTo(ArtGallery::class, 'gallery_id');
        }

        /**
         * Artist who created the piece.
         */
        public function artist(): BelongsTo
        {
            return $this->belongsTo(Artist::class, 'artist_id');
        }

        /**
         * Associated reviews for this specific piece of art.
         */
        public function reviews(): MorphMany
        {
            return $this->morphMany(ArtReview::class, 'reviewable');
        }

        /**
         * Helper to get price in display format.
         */
        public function getPriceAttribute(): float
        {
            return $this->price_cents / 100;
        }
}
