<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use App\Enums\TenantVerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Role;
use Illuminate\Support\Str;

final class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'type',
        'slug',
        'inn',
        'kpp',
        'ogrn',
        'legal_entity_type',
        'legal_address',
        'actual_address',
        'phone',
        'email',
        'website',
        'is_active',
        'is_verified',
        'verification_status',
        'verified_at',
        'moderator_notes',
        'timezone',
        'correlation_id',
        'uuid',
        'tags',
        'meta',
        'contact_locked_at',
        'is_primary_profile',
    ];

    protected $hidden = [
        'verification_code',
        'inn',   // Sensitive - encrypted at rest
        'kpp',   // Sensitive - encrypted at rest
        'ogrn',  // Sensitive - encrypted at rest
        'legal_address', // Sensitive - encrypted at rest
        'actual_address', // Sensitive - encrypted at rest
        'phone', // Sensitive - encrypted at rest
        'email', // Sensitive - encrypted at rest
    ];

    protected $casts = [
        'verification_status' => TenantVerificationStatus::class,
        'verified_at' => 'datetime',
        // ENCRYPTED CASTS - CatVRF 2026 Security Fortress
        'inn' => 'encrypted',
        'kpp' => 'encrypted',
        'ogrn' => 'encrypted',
        'legal_address' => 'encrypted',
        'actual_address' => 'encrypted',
        'phone' => 'encrypted',
        'email' => 'encrypted',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'timezone' => 'string',
        'tags' => 'json',
        'meta' => 'json',
        'contact_locked_at' => 'datetime',
        'is_primary_profile' => 'boolean',
    ];

    protected $table = 'tenants';

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * Tenant's users (team members)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->using(TenantUser::class)
            ->withPivot('role', 'is_active', 'invitation_token', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    /**
     * Active team members
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()
            ->where('tenant_user.is_active', true);
    }

    /**
     * Tenant's wallets
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'tenant_id');
    }

    /**
     * Main wallet (for this tenant)
     */
    public function mainWallet()
    {
        return $this->wallets()
            ->where('business_group_id', null)
            ->first();
    }

    /**
     * Business groups (филиалы)
     */
    public function businessGroups(): HasMany
    {
        return $this->hasMany(BusinessGroup::class, 'tenant_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByInn($query, string $inn)
    {
        return $query->where('inn', $inn);
    }

    public function scopeWithVerificationStatus($query, TenantVerificationStatus $status)
    {
        return $query->where('verification_status', $status);
    }

    public function scopePendingVerification($query)
    {
        return $query->whereIn('verification_status', [
            TenantVerificationStatus::Pending,
            TenantVerificationStatus::ManualReview,
        ]);
    }

    public function scopeCanOperate($query)
    {
        return $query->where('is_active', true)
            ->where('is_verified', true)
            ->whereIn('verification_status', [
                TenantVerificationStatus::Approved,
                TenantVerificationStatus::AutoApproved,
            ]);
    }

    // ========================
    // AUTHORIZATION HELPERS
    // ========================

    /**
     * Check if user can access this tenant
     */
    public function hasUser(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->users()
            ->where('user_id', $userId)
            ->where('tenant_user.is_active', true)
            ->exists();
    }

    /**
     * Get user's role in this tenant
     */
    public function getUserRole(?int $userId): ?Role
    {
        if (! $userId) {
            throw new \DomainException('Entity not found');
        }

        $user = $this->users()
            ->where('user_id', $userId)
            ->where('tenant_user.is_active', true)
            ->first();

        return $user?->pivot->role;
    }

    /**
     * Check if user has specific role(s) in tenant
     */
    public function userHasRole(?int $userId, Role|array $roles): bool
    {
        if (! $userId) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return $this->users()
            ->where('user_id', $userId)
            ->wherePivotIn('role', $roles)
            ->where('tenant_user.is_active', true)
            ->exists();
    }

    /**
     * Get owners (users with Owner role)
     */
    public function owners(): BelongsToMany
    {
        return $this->activeUsers()
            ->wherePivot('role', Role::Owner);
    }

    /**
     * Get managers (Owner + Manager)
     */
    public function managers(): BelongsToMany
    {
        return $this->activeUsers()
            ->wherePivotIn('role', [
                Role::Owner,
                Role::Manager,
            ]);
    }

    /**
     * Get display name
     */

    /**
     * Approve tenant verification
     */
    public function approveVerification(?string $moderatorNotes = null): bool
    {
        return $this->update([
            'verification_status' => TenantVerificationStatus::Approved,
            'is_active' => true,
            'is_verified' => true,
            'verified_at' => CarbonImmutable::now(),
            'moderator_notes' => $moderatorNotes,
        ]);
    }

    /**
     * Reject tenant verification
     */
    public function rejectVerification(string $reason): bool
    {
        return $this->update([
            'verification_status' => TenantVerificationStatus::Rejected,
            'is_active' => false,
            'is_verified' => false,
            'moderator_notes' => $reason,
        ]);
    }

    /**
     * Suspend tenant
     */
    public function suspend(string $reason): bool
    {
        return $this->update([
            'verification_status' => TenantVerificationStatus::Suspended,
            'is_active' => false,
            'moderator_notes' => $reason,
        ]);
    }

    /**
     * Check if tenant can operate
     */
    public function canOperate(): bool
    {
        return $this->is_active
            && $this->is_verified
            && $this->verification_status?->canOperate();
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} (ИНН: {$this->inn})";
    }

    // ========================
    // ATTRIBUTES & MUTATORS
    // ========================

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->uuid ??= Str::uuid()->toString();
            $model->slug ??= Str::slug($model->name);
        });
    }
}
