<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LabTestTypeModel extends Model
{
    use HasFactory;

    protected $table = 'dental_lab_test_types';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'category',
        'base_price',
        'processing_time_hours',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'processing_time_hours' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function labTests(): HasMany
    {
        return $this->hasMany(LabTestModel::class, 'lab_test_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
