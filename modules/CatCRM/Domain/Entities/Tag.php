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
 * Tag — Тег в CRM
 * 
 * Используется для маркировки клиентов, сделок и задач.
 */
final class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'name',
        'slug',
        'color',
        'icon',
        'description',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    protected $table = 'crm_tags';

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
        return $this->belongsToMany(Customer::class, 'crm_customer_tags');
    }

    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(Deal::class, 'crm_deal_tags');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
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
