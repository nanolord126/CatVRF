<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperInternalAudience
 */
final class InternalAudience extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'segmentation_type',
        'segmentation_rules',
        'master_id',
        'service_id',
        'estimated_size',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'segmentation_rules' => 'array',
        'is_active' => 'boolean',
    ];

    public const SEGMENTATION_ALL_CLIENTS = 'all_clients';
    public const SEGMENTATION_MASTER_CLIENTS = 'master_clients';
    public const SEGMENTATION_SERVICE_CLIENTS = 'service_clients';
    public const SEGMENTATION_ML_SEGMENT = 'ml_segment';
    public const SEGMENTATION_CUSTOM = 'custom';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('segmentation_type', $type);
    }

    public function scopeByMaster($query, int $masterId)
    {
        return $query->where('master_id', $masterId);
    }

    public function scopeByService($query, int $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function isMasterClients(): bool
    {
        return $this->segmentation_type === self::SEGMENTATION_MASTER_CLIENTS;
    }

    public function isServiceClients(): bool
    {
        return $this->segmentation_type === self::SEGMENTATION_SERVICE_CLIENTS;
    }

    public function isMLSegment(): bool
    {
        return $this->segmentation_type === self::SEGMENTATION_ML_SEGMENT;
    }
}
