<?php declare(strict_types=1);

namespace App\Domains\Bonuses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;

final class BonusTransaction extends Model
{
    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'user_id',
        'type',
        'amount',
        'status',
        'source_type',
        'source_id',
        'hold_until',
        'credited_at',
        'expires_at',
        'metadata',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'tags' => 'array',
        'hold_until' => 'datetime',
        'credited_at' => 'datetime',
        'expires_at' => 'datetime',
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

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCredited(): bool
    {
        return $this->status === 'credited';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isAvailable(): bool
    {
        return $this->isCredited() 
            && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCredited($query)
    {
        return $query->where('status', 'credited');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'credited')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
