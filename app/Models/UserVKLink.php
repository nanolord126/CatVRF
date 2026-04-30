<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserVKLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vk_user_id',
        'vk_username',
        'is_active',
        'last_notified_at',
        'verification_token',
        'verified_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function unlink(): void
    {
        $this->update(['is_active' => false]);
    }
}
