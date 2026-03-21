<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель портфолио мастера (фото работ).
 * Production 2026.
 */
final class PortfolioItem extends Model
{
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
