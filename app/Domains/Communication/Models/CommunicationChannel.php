<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Communication channel (email, sms, push, telegram, in_app).
 * Tenant-scoped.
 */
final class CommunicationChannel extends Model
{

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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
