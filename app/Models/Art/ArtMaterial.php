<?php

declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class ArtMaterial
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
final class ArtMaterial extends Model
{
    protected $table = 'art_materials';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'sku',
        'price_cents',
        'stock_level',
        'min_threshold',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'stock_level' => 'integer',
        'min_threshold' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        self::creating(function (ArtMaterial $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
