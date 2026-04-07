<?php declare(strict_types=1);

namespace App\Domains\Logistics\LogisticsConsulting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LogisticsProject
 *
 * Part of the Logistics vertical domain.
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
 * @package App\Domains\Logistics\LogisticsConsulting\Models
 */
final class LogisticsProject extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'logistics_projects';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'consultant_id',
        'client_id',
        'correlation_id',
        'name',
        'status',
        'start_date',
        'end_date',
        'budget',
        'tags',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'tags' => 'json',
        'meta' => 'json',
        'budget' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('logistics_projects.tenant_id', tenant()->id));
    }
}