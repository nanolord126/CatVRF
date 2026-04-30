<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserPushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_type', // web, ios, android
        'endpoint', // FCM token or Push subscription endpoint
        'keys', // JSON encoded keys for Web Push
        'is_active',
        'last_notified_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_notified_at' => 'datetime',
        'keys' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    public function markAsNotified(): void
    {
        $this->update(['last_notified_at' => now()]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
