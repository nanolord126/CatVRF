<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Models\Tenant;

/**
 * Communication channel (email, sms, push, telegram, in_app).
 * Tenant-scoped.
 */
final class CommunicationChannel extends Model
{
    use TenantScoped;

    protected $table = 'communication_channels';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'correlation_id',
        'name',
        'type',
        'config',
        'status',
        'tags',
    ];

    protected $casts = [
        'config' => 'array',
        'tags'   => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', static function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
