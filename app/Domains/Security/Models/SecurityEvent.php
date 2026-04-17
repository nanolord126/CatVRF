<?php declare(strict_types=1);

namespace App\Domains\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;

final class SecurityEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_type',
        'severity',
        'ip_address',
        'user_agent',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'metadata' => 'array',
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

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isWarning(): bool
    {
        return $this->severity === 'warning';
    }
}
