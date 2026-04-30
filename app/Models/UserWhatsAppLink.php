<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserWhatsAppLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'whatsapp_id',
        'is_active',
        'last_notified_at',
        'verification_code',
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

    public function generateVerificationCode(): string
    {
        $code = random_int(100000, 999999);
        $this->update(['verification_code' => $code]);
        return (string) $code;
    }

    public function verify(): void
    {
        $this->update([
            'verified_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Normalize phone number to international format
     */
    public function setPhoneAttribute($value): void
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $value);
        
        // Add +7 if Russian number without country code
        if (strlen($phone) === 10 && str_starts_with($phone, '9')) {
            $phone = '7' . $phone;
        } elseif (strlen($phone) === 11 && str_starts_with($phone, '8')) {
            $phone = '7' . substr($phone, 1);
        }
        
        $this->attributes['phone'] = $phone;
    }
}
