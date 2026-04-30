<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasOptimizedMedia;
use App\Traits\HasVideoCallSupport;
use Carbon\CarbonImmutable;

use App\Enums\Role;
use App\Services\Security\CryptoService;
use App\Enums\UserStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

final class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use HasOptimizedMedia;
    use HasVideoCallSupport;

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'email',
        'phone',
        'inn',
        'first_name',
        'last_name',
        'middle_name',
        'password_salt',
        'password_reset_required',
        'password',
        'role',
        'is_active',
        'is_a',
        'statusdmin',
        'email_verified_at',
        'phone_verified_at',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'verified_at',
        'verification_score',
        'verification_status',
        'verification_photo_media_id',
        'consent_data_processing',
        'consent_given_at',
        'correlation_id',
        'tags',
        'meta',
        'last_login_at',
        'last_activity_at',
        'category_preference',
        'face_reference_id',
        'face_verified_at',
        'is_locked',
        'last_failed_login_at',
        'failed_login_count',
        'locked_until',
        'revoked_at',
        'revocation_reason',
        'revoked_by',
        'device_fingerprint',
        'contact_locked_at',
        'is_primary_profile',
        // 152-FZ Biometric fields
        'face_id_consent_id',
        'behavioral_consent_id',
        'face_id_enabled',
        'behavioral_enabled',
        'biometric_face_vector',
        'biometric_behavioral_profile',
        // Restaurant loyalty fields
        'wallet_balance',
        'loyalty_tier',
        'visits_count',
        'total_spent',
        'average_check',
        'last_visit_at',
        'first_visit_at',
        'favorite_items',
        'allergies',
        'dietary_restrictions',
        'is_vip',
        'is_blacklisted',
        'blacklist_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_backup_codes',
        'email', // Sensitive - use masked accessor
        'phone', // Sensitive - use masked accessor
        'inn',   // Sensitive - encrypted at rest
    ];

    protected $casts = [
        'role' => Role::class,
        'status' => UserStatus::class,
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'contact_locked_at' => 'datetime',
        'is_primary_profile' => 'boolean',
        'tags' => 'json',
        'meta' => 'json',
        'face_verified_at' => 'datetime',
        // 152-FZ Biometric flags
        'face_id_enabled' => 'boolean',
        'behavioral_enabled' => 'boolean',
        // ENCRYPTED CASTS - CatVRF 2026 Security Fortress (152-FZ + ФСТЭК №21)
        'email' => 'encrypted',
        'phone' => 'encrypted',
        'inn' => 'encrypted',
        'first_name' => 'encrypted',
        'last_name' => 'encrypted',
        'middle_name' => 'encrypted',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted',
        'device_fingerprint' => 'encrypted',
        // Biometric vectors (st. 11 152-FZ: encrypted storage required)
        'biometric_face_vector' => 'encrypted',
        'biometric_behavioral_profile' => 'encrypted',
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

    /**
     * User's devices (for device management)
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class, 'user_id');
    }

    /**
     * User's Telegram links (for notifications)
     */
    public function telegramLinks(): HasMany
    {
        return $this->hasMany(UserTelegramLink::class, 'user_id');
    }

    /**
     * Active Telegram link
     */
    public function telegramLink(): HasMany
    {
        return $this->telegramLinks()->where('is_active', true);
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
        if (! $tenantId) {
            return false;
        }

        $query = $this->tenants()
            ->where('tenant_id', $tenantId)
            ->where('tenant_user.is_active', true);

        if ($roles !== null) {
            $roles = is_array($roles) ? $roles : [$roles];
            $roleValues = array_map(fn ($r) => $r instanceof Role ? $r->value : $r, $roles);
            $query->wherePivotIn('role', $roleValues);
        }

        return $query->exists();
    }

    /**
     * Get user's role in specific tenant
     */
    public function getRoleInTenant(?int $tenantId): ?Role
    {
        if (! $tenantId) {
            throw new \DomainException('Entity not found');
        }

        $tenant = $this->tenants()
            ->where('tenant_id', $tenantId)
            ->where('tenant_user.is_active', true)
            ->first();

        $roleValue = $tenant?->pivot->role;

        if ($roleValue) {
            return $roleValue instanceof Role ? $roleValue : Role::tryFrom($roleValue);
        }

        throw new \DomainException('Entity not found');
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

        if (! $tenantId) {
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
        if (! $this->is_active) {
            return false;
        }

        if ($panel->getId() === 'admin') {
            return $this->role?->isPlatformAdmin() ?? false;
        }

        if ($panel->getId() === 'tenant') {
         
 Check if user can login
      pbic funss-i
    public function revokeOtherDevices(int $currentDeviceId): bool
    {
        return $this->devices()
            ->where('id', '!=', $currentDeviceId)
            ->update(['is_revoked' => true]);
    }

    // ========================
    // VERIFICATION METHODS
    // ========================

    public function markAsVerified(float $score = 1.0): bool
    {
        return $this->update([
            'verification_status' => UserVerificationStatus::Verified,
            'verified_at' => CarbonImmutable::now(),
            'verification_score' => $score,
        ]);
    }

    public function markAsPending(): bool
    {
        return $this->update([
            'verification_status' => UserVerificationStatus::Pending,
        ]);
    }

    public function markAsRejected(string $reason): bool
    {
        return $this->update([
            'verification_status' => UserVerificationStatus::Rejected,
            'meta' => array_merge($this->meta ?? [], ['rejection_reason' => $reason]),
        ]);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === UserVerificationStatus::Verified
            && $this->verified_at !== null;
    }

    public function getFullName(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }

    public function hasGivenConsent(): bool
    {
        return $this->consent_data_processing
            && $this->consent_given_at !== null;
    }

    public function giveConsent(): bool
    {
        return $this->update([
            'consent_data_processing' => true,
            'consent_given_at' => CarbonImmutable::now(),
        ]);
    }

    // ========================
    // SECURITY METHODS
    // ========================

    /**
     * Check if account is locked due to brute-force attempts
     */
    public function isLockedOut(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * Lock account for specified duration
     */
    public function lockAccount(int $minutes = 60): bool
    {
        return $this->update([
            'locked_until' => CarbonImmutable::now()->addMinutes($minutes),
        ]);
    }

    /**
     * Unlock account
     */
    public function unlockAccount(): bool
    {
        return $this->update([
            'locked_until' => null,
            'failed_login_count' => 0,
        ]);
    }

    /**
     * Record failed login attempt
     */
    public function recordFailedLogin(): bool
    {
        return $this->update([
            'last_failed_login_at' => CarbonImmutable::now(),
            'failed_login_count' => $this->failed_login_count + 1,
        ]);
    }

    /**
     * Reset failed login count on successful login
     */
    public function resetFailedLogins(): bool
    {
        return $this->update([
            'failed_login_count' => 0,
            'last_failed_login_at' => null,
        ]);
    }

    /**
     * Check if access has been revoked (insider threat protection)
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Revoke user access (insider threat protection)
     */
    public function revokeAccess(int $revokedBy, string $reason): bool
    {
        return $this->update([
            'revoked_at' => CarbonImmutable::now(),
            'revocation_reason' => $reason,
            'revoked_by' => $revokedBy,
            'is_active' => false,
        ]);
    }

    /**
     * Restore user access
     */
    public function restoreAccess(): bool
    {
        return $this->update([
            'revoked_at' => null,
            'revocation_reason' => null,
            'revoked_by' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Check if user can perform login (not locked, not revoked, active)
     */
    public function canAuthenticate(): bool
    {
        return $this->is_active
            && ! $this->isLockedOut()
            && ! $this->isRevoked()
            && $this->canLogin();
    }

    // ========================
    // MUTATORS & ATTRIBUTES
    // ========================

    /**
     * Get masked email (e.g., u***@example.com)
     */
    public function getMaskedEmailAttribute(): string
    {
        if (! $this->email) {
            return '';
        }

        $parts = explode('@', $this->email);
        if (count($parts) !== 2) {
            return '***@***';
        }

        [$local, $domain] = $parts;
        $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(0, strlen($local) - 1));

        return $maskedLocal . '@' . $domain;
    }

    /**
     * Get masked phone (e.g., +7 (***) ***-**-45)
     */
    public function getMaskedPhoneAttribute(): string
    {
        if (! $this->phone) {
            return '';
        }

        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) < 10) {
            return '***';
        }

        // For Russian numbers: +7 (XXX) XXX-XX-45
        if (strlen($phone) === 11 && $phone[0] === '7' || $phone[0] === '8') {
            return '+7 (***') . substr($phone, -7, 3) . ') ***-**-' . substr($phone, -2);
        }

        // International format: +1 (***) ***-**-45
        $countryCode = substr($phone, 0, strlen($phone) - 10);
        return '+' . $countryCode . ' (***) ***-**-' . substr($phone, -2);
    }

    /**
     * Get masked INN (e.g., ********4567)
     */
    public function getMaskedInnAttribute(): string
    {
        if (! $this->inn) {
            return '';
        }

        $length = strlen($this->inn);
        $visibleChars = min(4, $length);

        return str_repeat('*', $length - $visibleChars) . substr($this->inn, -$visibleChars);
    }

    /**
     * Get full name (with masking for non-authorized access)
     */
    public function getFullNameAttribute(): string
    {
        // For basic access, return masked version
        if (! auth()->check() || ! auth()->user()?->role?->isPlatformAdmin()) {
            return $this->getMaskedName();
        }

        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }

    /**
     * Get masked name (e.g., И*** В***)
     */
    public function getMaskedNameAttribute(): string
    {
        $firstName = $this->first_name ? substr($this->first_name, 0, 1) . '***' : '';
        $lastName = $this->last_name ? substr($this->last_name, 0, 1) . '***' : '';

        return trim("{$lastName} {$firstName}");
    }

    /**
     * Get masked name internally
     */
    private function getMaskedName(): string
    {
        $firstName = $this->first_name ? substr($this->first_name, 0, 1) . '***' : '';
        $lastName = $this->last_name ? substr($this->last_name, 0, 1) . '***' : '';

        return trim("{$lastName} {$firstName}");
    }

    // ========================
    // JIT ACCESS METHODS (Insider Protection)
    // ========================

    /**
     * Check if user can view full client data (unmasked)
     * 
     * @return bool True if user has permission
     */
    public function canViewFullClientData(): bool
    {
        $currentUser = auth()->user();
        
        if (! $currentUser) {
            return false;
        }

        // Super-admins always have access
        if ($currentUser->role?->isPlatformAdmin()) {
            return true;
        }

        // Check for JIT access elevation
        if ($this->hasJitAccess($currentUser)) {
            return true;
        }

        // Check for explicit permission
        if ($currentUser->can('viewFullClientData')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has JIT (Just-In-Time) access elevation
     * 
     * @param  User  $requester  User requesting access
     * @return bool True if JIT access is active
     */
    public function hasJitAccess(User $requester): bool
    {
        $cacheKey = "jit_access:{$requester->id}:full_client_data";
        
        return cache()->has($cacheKey);
    }

    /**
     * Request JIT access elevation for viewing full client data
     * 
     * Requires Passkey and 2FA verification (if enabled in config).
     * 
     * @param  User  $requester  User requesting access
     * @return bool True if access granted
     * @throws \RuntimeException If verification fails
     */
    public function requestJitAccess(User $requester): bool
    {
        if (! config('insider-protection.data_access.enable_jit_access', true)) {
            throw new \RuntimeException('JIT access is disabled');
        }

        // Check if already has JIT access
        if ($this->hasJitAccess($requester)) {
            return true;
        }

        // Require Passkey verification if enabled
        if (config('insider-protection.data_access.jit_requires_passkey', true)) {
            if (! $this->verifyPasskey($requester)) {
                throw new \RuntimeException('Passkey verification required for JIT access');
            }
        }

        // Require 2FA verification if enabled
        if (config('insider-protection.data_access.jit_requires_2fa', true)) {
            if (! $requester->two_factor_enabled) {
                throw new \RuntimeException('2FA must be enabled for JIT access');
            }
        }

        // Grant JIT access for configured duration
        $durationMinutes = config('insider-protection.data_access.jit_access_duration_minutes', 60);
        $cacheKey = "jit_access:{$requester->id}:full_client_data";
        
        cache()->put($cacheKey, true, $durationMinutes * 60);

        // Log JIT access elevation
        $auditService = app(\App\Services\Security\AuditService::class);
        $auditService->logEvent('jit_access_elevated', [
            'user_id' => $requester->id,
            'target_user_id' => $this->id,
            'access_type' => 'full_client_data',
            'duration_minutes' => $durationMinutes,
        ], 'security');

        return true;
    }

    /**
     * Revoke JIT access for a user
     * 
     * @param  User  $requester  User whose access to revoke
     * @return bool True if revoked
     */
    public function revokeJitAccess(User $requester): bool
    {
        $cacheKey = "jit_access:{$requester->id}:full_client_data";
        $result = cache()->forget($cacheKey);

        if ($result) {
            // Log JIT access revocation
            $auditService = app(\App\Services\Security\AuditService::class);
            $auditService->logEvent('jit_access_revoked', [
                'user_id' => $requester->id,
                'revoked_by' => auth()->id(),
            ], 'security');
        }

        return $result;
    }

    /**
     * Verify Passkey for JIT access
     * 
     * @param  User  $requester  User to verify
     * @return bool True if verified
     */
    private function verifyPasskey(User $requester): bool
    {
        // In a real implementation, this would verify the Passkey challenge
        // For now, we'll check if the user has any Passkey registered
        return $requester->webauthnKeys()->count() > 0;
    }

    /**
     * Get masked version of sensitive field
     * 
     * @param  string  $field  Field name
     * @param  string  $value  Field value
     * @return string Masked value
     */
    public function getMaskedField(string $field, string $value): string
    {
        if ($this->canViewFullClientData()) {
            return $value;
        }

        $dbProtection = app(\App\Services\Security\DatabaseProtectionService::class);
        
        return $dbProtection->maskData($value, $field);
    }

    /**
     * Get email with masking based on permissions
     */
    public function getEmailWithPermission(): string
    {
        if (! $this->email) {
            return '';
        }

        return $this->canViewFullClientData() ? $this->email : $this->masked_email;
    }

    /**
     * Get phone with masking based on permissions
     */
    public function getPhoneWithPermission(): string
    {
        if (! $this->phone) {
            return '';
        }

        return $this->canViewFullClientData() ? $this->phone : $this->masked_phone;
    }

    /**
     * Get INN with masking based on permissions
     */
    public function getInnWithPermission(): string
    {
        if (! $this->inn) {
            return '';
        }

        return $this->canViewFullClientData() ? $this->inn : $this->masked_inn;
    }

    /**
     * Get full name with masking based on permissions
     */
    public function getFullNameWithPermission(): string
    {
        if ($this->canViewFullClientData()) {
            return $this->getFullName();
        }

        return $this->masked_name;
    }

    // ========================
    // RESTAURANT LOYALTY METHODS
    // ========================

    /**
     * Add wallet bonus for restaurant loyalty
     */
    public function addWalletBonus(float $amount, string $type, ?string $description = null): \Modules\Restaurant\Models\LoyaltyTransaction
    {
        $transaction = \Modules\Restaurant\Models\LoyaltyTransaction::create([
            'tenant_id' => $this->tenant_id,
            'restaurant_id' => null, // Will be set by service
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'loyalty_program_id' => null, // Will be set by service
            'guest_id' => $this->id,
            'type' => $type,
            'amount_change' => $amount,
            'balance_before' => $this->wallet_balance,
            'balance_after' => $this->wallet_balance + $amount,
            'description' => $description,
        ]);

        $this->wallet_balance += $amount;
        $this->updateLoyaltyTier();
        $this->save();

        return $transaction;
    }

    /**
     * Redeem wallet bonus
     */
    public function redeemWalletBonus(float $amount, ?string $description = null): \Modules\Restaurant\Models\LoyaltyTransaction
    {
        if ($this->wallet_balance < $amount) {
            throw new \InvalidArgumentException('Insufficient wallet balance');
        }

        return $this->addWalletBonus(-$amount, 'redeemed', $description);
    }

    /**
     * Update loyalty tier based on total spent
     */
    public function updateLoyaltyTier(): void
    {
        $this->loyalty_tier = match (true) {
            $this->total_spent >= 50000 => 'platinum',
            $this->total_spent >= 20000 => 'gold',
            $this->total_spent >= 5000 => 'silver',
            $this->total_spent >= 1000 => 'bronze',
            default => null,
        };
    }

    /**
     * Record restaurant visit
     */
    public function recordVisit(float $amount): void
    {
        $this->visits_count++;
        $this->total_spent += $amount;
        $this->average_check = $this->visits_count > 0 
            ? $this->total_spent / $this->visits_count 
            : 0;
        $this->last_visit_at = now();
        
        if (!$this->first_visit_at) {
            $this->first_visit_at = now();
        }
        
        $this->updateLoyaltyTier();
        $this->save();
    }

    /**
     * Add item to favorites
     */
    public function addToFavorites(int $menuItemId): void
    {
        $favorites = $this->favorite_items ?? [];
        
        if (!in_array($menuItemId, $favorites)) {
            $favorites[] = $menuItemId;
            $this->favorite_items = array_slice($favorites, -50); // Keep last 50
            $this->save();
        }
    }

    /**
     * Add to blacklist
     */
    public function addToBlacklist(string $reason): void
    {
        $this->is_blacklisted = true;
        $this->blacklist_reason = $reason;
        $this->save();
    }

    /**
     * Remove from blacklist
     */
    public function removeFromBlacklist(): void
    {
        $this->is_blacklisted = false;
        $this->blacklist_reason = null;
        $this->save();
    }

    protected static function boot()
    {
        parent::boot();

        // Generate UUID on create
        self::creating(function ($model) {
            $model->uuid ??= Str::uuid()->toString();
        });

        // Add global scopes for security
        static::addGlobalScope('tenant', new \App\Models\Scopes\TenantScope());
        static::addGlobalScope('client_data', new \App\Models\Scopes\ClientDataScope());
    }
}
