<?php declare(strict_types=1);

namespace App\Domains\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Tenant;
use App\Models\User;

final class Cart extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'seller_id',
        'status',
        'reserved_until',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'reserved_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isExpired(): bool
    {
        return $this->reserved_until !== null && $this->reserved_until->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('reserved_until', '<', now());
    }
}
