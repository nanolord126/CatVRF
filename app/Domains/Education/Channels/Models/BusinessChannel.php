<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BusinessChannel extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes, HasUuids;

        protected $table = 'business_channels';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'slug',
            'description',
            'avatar_url',
            'cover_url',
            'status',
            'archived_at',
            'last_post_at',
            'plan_id',
            'plan_expires_at',
            'subscribers_count',
            'posts_count',
            'tags',
            'correlation_id',
        ];

        protected $hidden = [];

        protected $casts = [
            'archived_at'       => 'datetime',
            'last_post_at'      => 'datetime',
            'plan_expires_at'   => 'datetime',
            'subscribers_count' => 'integer',
            'posts_count'       => 'integer',
            'tags'              => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope(
                'tenant',
                fn ($query) => $query->where('business_channels.tenant_id', tenant('id') ?? '0')
            );
        }

        // ──────────────────────────────────────────────────────
        // Связи
        // ──────────────────────────────────────────────────────

        public function plan(): BelongsTo
        {
            return $this->belongsTo(ChannelSubscriptionPlan::class, 'plan_id');
        }

        public function posts(): HasMany
        {
            return $this->hasMany(Post::class, 'channel_id');
        }

        public function publishedPosts(): HasMany
        {
            return $this->posts()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        }

        public function subscribers(): HasMany
        {
            return $this->hasMany(ChannelSubscriber::class, 'channel_id')
                ->whereNull('unsubscribed_at');
        }

        public function subscriptionUsages(): HasMany
        {
            return $this->hasMany(ChannelSubscriptionUsage::class, 'channel_id');
        }

        public function activeSubscription(): HasMany
        {
            return $this->subscriptionUsages()
                ->where('status', 'active')
                ->where('expires_at', '>', now());
        }

        // ──────────────────────────────────────────────────────
        // Helpers
        // ──────────────────────────────────────────────────────

        public function isActive(): bool
        {
            return $this->status === 'active';
        }

        public function isArchived(): bool
        {
            return $this->status === 'archived';
        }

        public function hasPlan(): bool
        {
            return $this->plan_id !== null
                && $this->plan_expires_at !== null
                && $this->plan_expires_at->isFuture();
        }

        public function getPlanSlug(): string
        {
            return $this->plan?->slug ?? 'basic';
        }
}
