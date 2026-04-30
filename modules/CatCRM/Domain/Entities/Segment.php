<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;

/**
 * Segment — Сегмент клиентов в CRM
 * 
 * Используется для сегментации клиентов по различным критериям.
 */
final class Segment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'name',
        'slug',
        'description',
        'conditions', // JSON с условиями сегментации
        'is_dynamic', // Динамический сегмент (пересчитывается)
        'is_active',
        'customer_count',
        'last_calculated_at',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'conditions' => 'json',
        'is_dynamic' => 'boolean',
        'is_active' => 'boolean',
        'customer_count' => 'integer',
        'last_calculated_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $table = 'crm_segments';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'crm_customer_segments');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDynamic($query)
    {
        return $query->where('is_dynamic', true);
    }

    public function scopeStatic($query)
    {
        return $query->where('is_dynamic', false);
    }

    // ========================
    // METHODS
    // ========================

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            $model->slug ??= \Illuminate\Support\Str::slug($model->name);
        });
    }
}
