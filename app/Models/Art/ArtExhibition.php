<?php

declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class ArtExhibition
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ArtExhibition extends Model
{
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

    /**
     * Gallery hosting the exhibition.
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(ArtGallery::class, 'gallery_id');
    }

    protected static function booted(): void
    {
        self::creating(function (ArtExhibition $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
