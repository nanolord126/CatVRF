<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

abstract class BaseDomainModel extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id', 'uuid', 'tenant_id', 'business_group_id'];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Enforce Tenant Isolation globally
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        // Enforce Business Group Isolation globally
        static::addGlobalScope('businessGroup', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->business_group_id) {
                $builder->where('business_group_id', tenant()?->business_group_id);
            }
        });

        // Auto-generate UUID
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope for Business Group (Sub-tenancy) if active
     */
    public function scopeInBusinessGroup(Builder $query, int $businessGroupId): Builder
    {
        return $query->where('business_group_id', $businessGroupId);
    }
}
