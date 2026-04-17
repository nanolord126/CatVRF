<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * CRM Segment — группа клиентов по правилам фильтрации.
 * Может быть динамическим (пересчитывается автоматически) или статическим.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmSegment extends Model
{


    protected static function newFactory(): \Database\Factories\CRM\CrmSegmentFactory
    {
        return \Database\Factories\CRM\CrmSegmentFactory::new();
    }
    protected $table = 'crm_segments';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'correlation_id',
        'tags',
        'name',
        'slug',
        'description',
        'vertical',
        'is_dynamic',
        'rules',
        'clients_count',
        'last_calculated_at',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'json',
        'rules' => 'json',
        'is_dynamic' => 'boolean',
        'is_active' => 'boolean',
        'clients_count' => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
            if (!$model->slug) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(CrmClient::class, 'crm_client_segment', 'crm_segment_id', 'crm_client_id')
            ->withTimestamps();
    }
}
