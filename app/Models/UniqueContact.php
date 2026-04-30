<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProfileType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unique Contact Model
 *
 * Enforces zero-duplicate policy: one email/phone = one profile (client OR business).
 * Contacts are hashed for 152-ФZ / GDPR compliance.
 *
 * @property int $id
 * @property string|null $email_hashed
 * @property string|null $phone_hashed
 * @property ProfileType $profile_type
 * @property string $entity_type
 * @property int $entity_id
 * @property int|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property \Illuminate\Support\Carbon|null $released_at
 * @property \Illuminate\Support\Carbon $registered_at
 * @property \Illuminate\Support\Carbon|null $last_verified_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class UniqueContact extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'email_hashed',
        'phone_hashed',
        'profile_type',
        'entity_type',
        'entity_id',
        'tenant_id',
        'locked_at',
        'released_at',
        'registered_at',
        'last_verified_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'profile_type' => ProfileType::class,
        'locked_at' => 'datetime',
        'released_at' => 'datetime',
        'registered_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    protected $table = 'unique_contacts';

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * The entity that owns this contact (User or BusinessGroup)
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The tenant (for business profiles)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * User who created this contact record
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated this contact record
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope for client profiles
     */
    public function scopeClient($query)
    {
        return $query->where('profile_type', ProfileType::Client);
    }

    /**
     * Scope for business profiles
     */
    public function scopeBusiness($query)
    {
        return $query->where('profile_type', ProfileType::Business);
    }

    /**
     * Scope for locked contacts
     */
    public function scopeLocked($query)
    {
        return $query->whereNotNull('locked_at')
            ->where(function ($q) {
                $q->whereNull('released_at')
                    ->orWhere('released_at', '>', now());
            });
    }

    /**
     * Scope for available (unlocked) contacts
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('locked_at')
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('released_at')
                        ->where('released_at', '<=', now());
                });
        });
    }

    /**
     * Scope by email hash
     */
    public function scopeByEmailHash($query, string $emailHash)
    {
        return $query->where('email_hashed', $emailHash);
    }

    /**
     * Scope by phone hash
     */
    public function scopeByPhoneHash($query, string $phoneHash)
    {
        return $query->where('phone_hashed', $phoneHash);
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Check if contact is currently locked
     */
    public function isLocked(): bool
    {
        if ($this->locked_at === null) {
            return false;
        }

        if ($this->released_at === null) {
            return true;
        }

        return $this->released_at->isFuture();
    }

    /**
     * Lock contact for specified duration
     */
    public function lock(int $hours = 72): bool
    {
        return $this->update([
            'locked_at' => now(),
            'released_at' => now()->addHours($hours),
        ]);
    }

    /**
     * Unlock contact
     */
    public function unlock(): bool
    {
        return $this->update([
            'locked_at' => null,
            'released_at' => null,
        ]);
    }

    /**
     * Check if contact belongs to client profile
     */
    public function isClientProfile(): bool
    {
        return $this->profile_type === ProfileType::Client;
    }

    /**
     * Check if contact belongs to business profile
     */
    public function isBusinessProfile(): bool
    {
        return $this->profile_type === ProfileType::Business;
    }

    /**
     * Mark as verified
     */
    public function markAsVerified(): bool
    {
        return $this->update([
            'last_verified_at' => now(),
        ]);
    }
}
