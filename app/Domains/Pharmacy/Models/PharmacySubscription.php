<?php

declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class PharmacySubscription
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
final class PharmacySubscription extends Model
{
    use TenantScoped;

    protected $table = 'pharmacy_subscriptions';

    protected $fillable = ['uuid', 'tenant_id', 'user_id', 'medicine_id', 'frequency', 'status', 'correlation_id', 'tags'];

    protected $casts = ['tags' => 'json'];

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    protected static function booted(): void
    {
        self::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
        self::addGlobalScope('tenant', fn (Builder $b) => $b->where('tenant_id', tenant()->id));
    }
}
