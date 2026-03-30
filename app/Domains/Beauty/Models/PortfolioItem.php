<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PortfolioItem extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids;

        protected $table = 'portfolio_items';

        protected $fillable = [
            'tenant_id',
            'master_id',
            'title',
            'description',
            'before_image_path',
            'after_image_path',
            'service_type',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $hidden = [];

        protected $casts = [
            'tags' => 'collection',
            'metadata' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
        }

        public function master(): BelongsTo
        {
            return $this->belongsTo(Master::class, 'master_id');
        }
}
