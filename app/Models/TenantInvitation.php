<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TenantInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invited_by_user_id',
        'email',
        'name',
        'role',
        'token',
        'accepted_at',
        'expires_at',
        'is_accepted',
        'is_expired',
        'message',
        'meta',
    ];

    protected $casts = [
        'role' => Role::class,
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_accepted' => 'boolean',
        'is_expired' => 'boolean',
        'meta' => 'json',
    ];

    protected $table = 'tenant_invitations';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopePending($query)
    {
        return $query->where('is_accepted', false)->where('is_expired', false);
    }

    public function scopeAccepted($query)
    {
        return $query->where('is_accepted', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('is_expired', true);
    }

    public function scopeByToken($query, string $token)
    {
        return $query->where('token', $token);
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Accept invitation
     */
    public function accept(): bool
    {
        return $this->update([
            'is_accepted' => true,
            'accepted_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * Mark invitation as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update(['is_expired' => true]);
    }

    /**
     * Check if invitation is valid
     */
    public function isValid(): bool
    {
        return ! $this->is_accepted && ! $this->is_expired && $this->expires_at->isFuture();
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->is_expired || $this->expires_at->isPast();
    }

    /**
     * Generate acceptance URL
     */
    public function getAcceptanceUrl(): string
    {
        return route('tenant.invitations.accept', ['token' => $this->token]);
    }

    // ========================
    // METHODS
    // ========================

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->token ??= Str::random(64);
            $model->expires_at ??= CarbonImmutable::now()->addDays(7);
        });
    }
}
