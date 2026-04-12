<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Staff
 *
 * Part of the Staff vertical domain.
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
 * @package App\Domains\Staff\Domain\Entities
 */
final class Staff extends Model
{
    public function __construct(
        private readonly Guard $guard) {}

    use HasUuids;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'business_group_id',
        'role',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'role' => StaffRole::class,
        'tags' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($this->guard->check() && $this->guard->user()->isTenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
