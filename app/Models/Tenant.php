<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Model;

final class Tenant extends Model
{
    use HasFactory, SoftDeletes;

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
        'verification_code',
        'timezone',
        'correlation_id',
        'uuid',
        'tags',
        'meta',
    ];

    protected $hidden = [
        'verification_code',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'timezone' => 'string',
        'tags' => 'json',
        'meta' => 'json',
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

    // ========================
    // AUTHORIZATION HELPERS
    // ========================

    /**
     * Check if user can access this tenant
     */
    public function hasUser(?int $userId): bool
    {
        if (!$userId) {
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
    public function getUserRole(?int $userId): ?\App\Enums\Role
    {
        if (!$userId) {
            return null;
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
    public function userHasRole(?int $userId, \App\Enums\Role|array $roles): bool
    {
        if (!$userId) {
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
            ->wherePivot('role', \App\Enums\Role::Owner);
    }

    /**
     * Get managers (Owner + Manager)
     */
    public function managers(): BelongsToMany
    {
        return $this->activeUsers()
            ->wherePivotIn('role', [
                \App\Enums\Role::Owner,
                \App\Enums\Role::Manager,
            ]);
    }

    // ========================
    // ATTRIBUTES & MUTATORS
    // ========================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            $model->slug ??= \Illuminate\Support\Str::slug($model->name);
        });
    }

    /**
     * Get display name
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} (ИНН: {$this->inn})";
    }
}
