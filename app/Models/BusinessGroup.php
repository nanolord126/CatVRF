<?php declare(strict_types=1);

namespace App\Models;

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
        'name',
        'inn',
        'kpp',
        'legal_address',
        'actual_address',
        'phone',
        'email',
        'is_active',
        'is_verified',
        'commission_percent',
        'correlation_id',
        'uuid',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
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
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
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

        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
