<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class TenantUser extends Pivot
{
    protected $table = 'tenant_user';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'role',
        'is_active',
        'invitation_token',
        'invited_at',
        'accepted_at',
        'correlation_id',
    ];

    protected $casts = [
        'role' => Role::class,
        'is_active' => 'boolean',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public $timestamps = true;

    // ========================
    // RELATIONSHIPS
    // ========================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at');
    }

    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Accept invitation
     */
    public function accept(): bool
    {
        return $this->update([
            'accepted_at' => now(),
            'is_active' => true,
            'invitation_token' => null,
        ]);
    }

    /**
     * Decline invitation
     */
    public function decline(): bool
    {
        return $this->delete();
    }

    /**
     * Check if invitation is pending
     */
    public function isPending(): bool
    {
        return $this->accepted_at === null;
    }

    /**
     * Check if user can manage this tenant
     */
    public function canManage(): bool
    {
        return $this->role->isTenantAdmin() && $this->is_active;
    }
}
