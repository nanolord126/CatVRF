<?php declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ArtExhibition extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'art_exhibitions';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'gallery_id',
            'title',
            'description',
            'starts_at',
            'ends_at',
            'is_virtual',
            'entry_fee_cents',
            'correlation_id',
        ];

        protected $casts = [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_virtual' => 'boolean',
            'entry_fee_cents' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (ArtExhibition $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

        /**
         * Gallery hosting the exhibition.
         */
        public function gallery(): BelongsTo
        {
            return $this->belongsTo(ArtGallery::class, 'gallery_id');
        }
}
