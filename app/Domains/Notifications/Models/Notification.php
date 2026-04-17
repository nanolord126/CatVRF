<?php declare(strict_types=1);

namespace App\Domains\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;

final class Notification extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'channel',
        'title',
        'body',
        'data',
        'read_at',
        'delivered_at',
        'failed_at',
        'error_message',
        'correlation_id',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
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

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    public function isFailed(): bool
    {
        return $this->failed_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }
}
