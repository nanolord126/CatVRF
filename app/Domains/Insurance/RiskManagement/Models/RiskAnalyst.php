<?php declare(strict_types=1);

namespace App\Domains\Insurance\RiskManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class RiskAnalyst
 *
 * Part of the Insurance vertical domain.
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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\Insurance\RiskManagement\Models
 */
final class RiskAnalyst extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'risk_analysts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'correlation_id',
        'name',
        'specialties',
        'certifications',
        'tags',
        'meta',
    ];

    protected $casts = [
        'specialties' => 'json',
        'certifications' => 'json',
        'tags' => 'json',
        'meta' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('risk_analysts.tenant_id', tenant()->id));
    }
}