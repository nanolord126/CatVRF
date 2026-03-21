<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;

final class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'is_admin',
        'email_verified_at',
        'phone_verified_at',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'correlation_id',
        'tags',
        'meta',
        'last_login_at',
        'last_activity_at',
        'category_preference',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_backup_codes',
    ];

    protected $casts = [
        'role' => Role::class,
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'tags' => 'json',
        'meta' => 'json',
    ];

    protected $table = 'users';

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * User's tenant assignments (business roles)
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot('role', 'is_active', 'invitation_token', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    /**
     * User's wallets (for customers)
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    /**
     * User's payment transactions (as customer)
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'user_id');
    }

    /**
     * User's balance transactions
     */
    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class, 'user_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopePlatformAdmins($query)
    {
        return $query->whereIn('role', [Role::SuperAdmin, Role::SupportAgent]);
    }

    public function scopeCustomers($query)
    {
        return $query->where('role', Role::Customer);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // ========================
    // AUTHORIZATION HELPERS
    // ========================

    /**
     * Check if user is platform admin
     */
    public function isPlatformAdmin(): bool
    {
        return $this->role?->isPlatformAdmin() ?? false;
    }

    /**
     * Check if user is business (owner/manager/employee/accountant)
     */
    public function isBusiness(): bool
    {
        return $this->role?->isBusiness() ?? false;
    }

    /**
     * Check if user has role in specific tenant
     */
    public function hasRoleInTenant(?int $tenantId, Role|array|null $roles = null): bool
    {
        if (!$tenantId) {
            return false;
        }

        $query = $this->tenants()
            ->where('tenant_id', $tenantId)
            ->where('tenant_user.is_active', true);

        if ($roles !== null) {
            $roles = is_array($roles) ? $roles : [$roles];
            $roleValues = array_map(fn($r) => $r instanceof Role ? $r->value : $r, $roles);
            $query->wherePivotIn('role', $roleValues);
        }

        return $query->exists();
    }

    /**
     * Get user's role in specific tenant
     */
    public function getRoleInTenant(?int $tenantId): ?Role
    {
        if (!$tenantId) {
            return null;
        }

        $tenant = $this->tenants()
            ->where('tenant_id', $tenantId)
            ->where('tenant_user.is_active', true)
            ->first();

        $roleValue = $tenant?->pivot->role;
        
        if ($roleValue) {
            return $roleValue instanceof Role ? $roleValue : Role::tryFrom($roleValue);
        }
        
        return null;
    }

    /**
     * Get all tenants where user has owner role
     */
    public function ownedTenants(): BelongsToMany
    {
        return $this->tenants()
            ->wherePivot('role', Role::Owner)
            ->wherePivot('is_active', true);
    }

    /**
     * Get all tenants where user has manager/admin roles
     */
    public function managedTenants(): BelongsToMany
    {
        return $this->tenants()
            ->wherePivotIn('role', [Role::Owner, Role::Manager])
            ->wherePivot('is_active', true);
    }

    /**
     * Get active tenant (for session)
     */
    public function getActiveTenant(): ?Tenant
    {
        $tenantId = session('active_tenant_id');
        
        if (!$tenantId) {
            return $this->tenants()
                ->where('tenant_user.is_active', true)
                ->first();
        }

        return $this->tenants()
            ->where('tenant_id', $tenantId)
            ->where('tenant_user.is_active', true)
            ->first();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($panel->getId() === 'admin') {
            return $this->role?->isPlatformAdmin() ?? false;
        }

        if ($panel->getId() === 'tenant') {
            return $this->role?->isBusiness() ?? false;
        }

        return true;
    }

    // ========================
    // MUTATORS & ATTRIBUTES
    // ========================

    protected static function boot()
    {
        parent::boot();

        // Generate UUID on create
        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid();
        });
    }
}


