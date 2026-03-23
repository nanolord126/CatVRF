<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Blogger Profile (Talent/Creator)
 */
class BloggerProfile extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blogger_profiles';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'business_group_id',
        'display_name',
        'biography',
        'profile_picture_url',
        'banner_url',
        'verification_status',
        'inn',
        'documents',
        'primary_category',
        'tags',
        'correlation_id',
        'total_streams',
        'total_viewers',
        'total_earned',
        'wallet_balance',
        'monetization_settings',
        'last_stream_at',
        'is_active',
    ];

    protected $casts = [
        'documents' => 'json',
        'tags' => 'json',
        'monetization_settings' => 'json',
        'total_earned' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
        'last_stream_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['correlation_id'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('blogger_profiles.tenant_id', tenant()->id);
        });

        if (\Filament\Facades\Filament::getTenant()) {
            $businessGroup = \Filament\Facades\Filament::getTenant()->active_business_group;
            if ($businessGroup) {
                static::addGlobalScope('businessGroup', function ($query) {
                    $query->where(function ($q) {
                        $q->whereNull('business_group_id')
                            ->orWhere('business_group_id', filament()->getTenant()->active_business_group?->id);
                    });
                });
            }
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class, 'blogger_id');
    }

    public function liveStreams(): HasMany
    {
        return $this->streams()->where('status', 'live');
    }

    public function vodStreams(): HasMany
    {
        return $this->streams()->whereIn('status', ['vod', 'archived']);
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(BloggerVerificationDocument::class, 'blogger_id');
    }

    public function nftGiftsReceived(): HasMany
    {
        return $this->hasMany(NftGift::class, 'recipient_user_id');
    }

    public function getTotalRevenueAttribute(): int
    {
        return (int) $this->total_earned;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function canStream(): bool
    {
        return $this->is_active && $this->isVerified();
    }
}
