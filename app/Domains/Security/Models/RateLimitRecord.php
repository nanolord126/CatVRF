<?php declare(strict_types=1);

namespace App\Domains\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;

final class RateLimitRecord extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'operation',
        'key',
        'attempts',
        'limit',
        'window_seconds',
        'blocked_until',
        'correlation_id',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
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

    public function isBlocked(): bool
    {
        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    public function isExceeded(): bool
    {
        return $this->attempts > $this->limit;
    }
}
