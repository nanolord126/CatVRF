<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Stream extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'streams';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'blogger_id',
            'business_group_id',
            'title',
            'description',
            'thumbnail_url',
            'status',
            'room_id',
            'broadcast_key',
            'broadcast_url',
            'scheduled_at',
            'started_at',
            'ended_at',
            'viewer_count',
            'peak_viewers',
            'duration_seconds',
            'total_revenue',
            'platform_commission',
            'tags',
            'correlation_id',
            'hls_playlist_url',
            'vod_path',
            'record_stream',
            'allow_chat',
            'allow_gifts',
            'allow_commerce',
        ];

        protected $casts = [
            'tags' => 'json',
            'total_revenue' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'record_stream' => 'boolean',
            'allow_chat' => 'boolean',
            'allow_gifts' => 'boolean',
            'allow_commerce' => 'boolean',
        ];

        protected $hidden = ['broadcast_key', 'correlation_id'];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('streams.tenant_id', tenant()->id);
            });
        }

        public function blogger(): BelongsTo
        {
            return $this->belongsTo(BloggerProfile::class, 'blogger_id');
        }

        public function products(): HasMany
        {
            return $this->hasMany(StreamProduct::class, 'stream_id');
        }

        public function pinnedProducts(): HasMany
        {
            return $this->products()->where('is_pinned', true)->orderBy('pin_position');
        }

        public function orders(): HasMany
        {
            return $this->hasMany(StreamOrder::class, 'stream_id');
        }

        public function chatMessages(): HasMany
        {
            return $this->hasMany(StreamChatMessage::class, 'stream_id')->orderBy('created_at');
        }

        public function nftGifts(): HasMany
        {
            return $this->hasMany(NftGift::class, 'stream_id');
        }

        public function statistics(): \Illuminate\Database\Eloquent\Relations\HasOne
        {
            return $this->hasOne(StreamStatistics::class, 'stream_id');
        }

        public function isLive(): bool
        {
            return $this->status === 'live';
        }

        public function isScheduled(): bool
        {
            return $this->status === 'scheduled';
        }

        public function isEnded(): bool
        {
            return $this->status === 'ended';
        }

        public function canAcceptGifts(): bool
        {
            return $this->isLive() && $this->allow_gifts;
        }

        public function canAcceptOrders(): bool
        {
            return $this->isLive() && $this->allow_commerce;
        }
}
