<?php declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use App\Enums\BusinessGroupVerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BusinessGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'parent_business_group_id',
        'name',
        'inn',
        'kpp',
        'legal_address',
        'actual_address',
        'phone',
        'email',
        'is_active',
        'is_verified',
        'verification_status',
        'inn_verified_at',
        'is_branch',
        'commission_percent',
        'moderator_notes',
        'correlation_id',
        'uuid',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_branch' => 'boolean',
        'verification_status' => BusinessGroupVerificationStatus::class,
        'inn_verified_at' => 'datetime',
        'commission_percent' => 'float',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'business_groups';

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * Parent tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Parent business group (for branches)
     */
    public function parentBusinessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'parent_business_group_id');
    }

    /**
     * Child business groups (branches)
     */
    public function childBusinessGroups(): HasMany
    {
        return $this->hasMany(BusinessGroup::class, 'parent_business_group_id');
    }

    /**
     * Business group's wallets
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'business_group_id');
    }

    /**
     * Main wallet for this business group
     */
    public function mainWallet()
    {
        return $this->wallets()->first();
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    public function scopeBranches($query)
    {
        return $query->where('is_branch', true);
    }

    public function scopeMain($query)
    {
        return $query->where('is_branch', false);
    }

    public function scopeByVerificationStatus($query, BusinessGroupVerificationStatus $status)
    {
        return $query->where('verification_status', $status);
    }

    public function scopePendingVerification($query)
    {
        return $query->whereIn('verification_status', [
            BusinessGroupVerificationStatus::Pending,
            BusinessGroupVerificationStatus::ManualReview,
        ]);
    }


    // ========================
    // VERIFICATION METHODS
    // ========================

    public function approveVerification(?string $moderatorNotes = null): bool
    {
        return $this->update([
            'verification_status' => BusinessGroupVerificationStatus::Approved,
            'is_verified' => true,
            'is_active' => true,
            'moderator_notes' => $moderatorNotes,
        ]);
    }

    public function rejectVerification(string $reason): bool
    {
        return $this->update([
            'verification_status' => BusinessGroupVerificationStatus::Rejected,
            'is_verified' => false,
            'is_active' => false,
            'moderator_notes' => $reason,
        ]);
    }

    public function markInnVerified(): bool
    {
        return $this->update([
            'inn_verified_at' => CarbonImmutable::now(),
        ]);
    }

    public function canOperate(): bool
    {
        return $this->is_active
            && $this->is_verified
            && $this->verification_status?->canOperate();
    }

    public function isBranch(): bool
    {
        return $this->is_branch;
    }

    public function isMain(): bool
    {
        return !$this->is_branch;
    }
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by business group ID (for B2B reports/analytics)
     * This prevents data leakage when querying from B2B context
     */
    public function scopeByBusinessGroup($query, ?int $businessGroupId)
    {
        if ($businessGroupId === null) {
            return $query;
        }

        return $query->where('id', $businessGroupId);
    }

    // ========================
    // ATTRIBUTES & MUTATORS
    // ========================

    /**
     * Get display name
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} (ИНН: {$this->inn})";
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
        });

        // Global scope for tenant_id is handled by trait
        // This ensures consistency across all tenant-scoped models
    }
}
