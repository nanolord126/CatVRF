<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ManagerClientAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'manager_id',
        'business_id',
        'tenant_id',
        'vertical_id',
        'is_primary',
        'assigned_at',
        'unassigned_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relations
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('unassigned_at');
    }

    public function scopeInactive($query)
    {
        return $query->whereNotNull('unassigned_at');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForVertical($query, $verticalId)
    {
        return $query->where('vertical_id', $verticalId);
    }

    public function scopeForManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    // Methods
    public function isActive(): bool
    {
        return $this->unassigned_at === null;
    }

    public function unassign(): void
    {
        $this->unassigned_at = now();
        $this->save();
    }
}
