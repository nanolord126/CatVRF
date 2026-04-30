<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Carbon\Carbon;

/**
 * Warehouse License Model
 *
 * Stores warehouse licenses per ФЗ-323 (pharmaceutical activity compliance).
 * Tracks license types, expiry dates, and status for regulatory compliance.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property string $license_type
 * @property string $license_number
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon $expiry_date
 * @property string $status
 * @property string $issuing_authority
 * @property string|null $license_scope
 * @property array|null $restrictions
 * @property bool $has_temporary_restrictions
 * @property string|null $suspension_reason
 * @property \Illuminate\Support\Carbon|null $suspension_date
 * @property int $tenant_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class WarehouseLicense extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'warehouse_licenses';

    protected $fillable = [
        'warehouse_id',
        'license_type',
        'license_number',
        'issue_date',
        'expiry_date',
        'status',
        'issuing_authority',
        'license_scope',
        'restrictions',
        'has_temporary_restrictions',
        'suspension_reason',
        'suspension_date',
        'tenant_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'suspension_date' => 'date',
        'restrictions' => 'json',
        'has_temporary_restrictions' => 'boolean',
    ];

    /**
     * Warehouse this license belongs to
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Tenant this license belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope for active licenses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pharmaceutical licenses
     */
    public function scopePharmaceutical($query)
    {
        return $query->where('license_type', 'pharmaceutical');
    }

    /**
     * Scope for narcotic licenses
     */
    public function scopeNarcotic($query)
    {
        return $query->where('license_type', 'narcotic');
    }

    /**
     * Scope for expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    /**
     * Scope for expired licenses
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now()->startOfDay());
    }

    /**
     * Check if license is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if license is expiring soon
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && 
               $this->expiry_date->lte(now()->addDays($days)) && 
               $this->expiry_date->gt(now());
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if license is currently valid
     */
    public function isValid(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }
}
