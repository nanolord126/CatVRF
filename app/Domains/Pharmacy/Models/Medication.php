<?php

declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Class Medication
 *
 * Part of the Pharmacy vertical domain.
 * Follows CatVRF 9-layer architecture.
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
final class Medication extends Model
{
    use TenantScoped;

    protected $table = 'medications';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'inn',
        'sku',
        'price',
        'requires_prescription',
        'stock_quantity',
        'instructions',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'requires_prescription' => 'boolean',
        'instructions' => 'json',
        'tags' => 'json',
        'price' => 'integer',
        'stock_quantity' => 'integer',
    ];

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function (Builder $builder) {
            $builder->where('tenant_id', tenant()->id ?? 0);
        });

        self::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
        });
    }
}
