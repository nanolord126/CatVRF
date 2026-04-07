<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * PortfolioItem — элемент портфолио мастера (фото до/после).
 *
 * @property int    $id
 * @property string $uuid
 * @property int    $tenant_id
 * @property int    $master_id
 * @property string $title
 * @property string $description
 * @property string $before_image_path
 * @property string $after_image_path
 * @property string $service_type
 * @property string $correlation_id
 * @property array  $tags
 * @property array  $metadata
 */
final class PortfolioItem extends Model
{
    protected $table = 'portfolio_items';

    protected $fillable = [
        'uuid',
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
        'tags'     => 'array',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', static function ($query): void {
            if ($tenantId = tenant()->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    public function hasBeforeAfterPhotos(): bool
    {
        return ! empty($this->before_image_path) && ! empty($this->after_image_path);
    }

    public function hasBeforePhoto(): bool
    {
        return ! empty($this->before_image_path);
    }

    public function hasAfterPhoto(): bool
    {
        return ! empty($this->after_image_path);
    }
}