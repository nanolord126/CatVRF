<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserEmailPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'is_active',
        'order_notifications',
        'promotional_notifications',
        'last_notified_at',
        'verified_at',
        'verification_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_notifications' => 'boolean',
        'promotional_notifications' => 'boolean',
        'last_notified_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeOrderNotifications($query)
    {
        return $query->where('order_notifications', true);
    }

    public function markAsNotified(): void
    {
        $this->update(['last_notified_at' => now()]);
    }

    public function generateVerificationToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['verification_token' => $token]);
        return $token;
    }

    public function verify(): void
    {
        $this->update([
            'verified_at' => now(),
            'is_active' => true,
        ]);
    }
}
