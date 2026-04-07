<?php declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StreamPeerConnection extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'stream_peer_connections';

        protected $fillable = [
            'uuid',
            'stream_id',
            'tenant_id',
            'user_id',
            'peer_id',
            'ice_candidates',
            'local_sdp',
            'remote_sdp',
            'status',
            'connection_type',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'ice_candidates' => 'json',
            'tags' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = [
            'local_sdp',
            'remote_sdp',
            'ice_candidates',
        ];

/**
         * Relationship: Belongs to Stream/Event
         */
        public function stream(): BelongsTo
        {
            return $this->belongsTo(\App\Domains\Education\Bloggers\Models\Stream::class, 'stream_id');
        }

        /**
         * Relationship: Belongs to User (peer)
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        /**
         * Relationship: Belongs to Tenant
         */
        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        /**
         * Scope: Get connected peers for a stream
         */
        public function scopeConnected(Builder $query): Builder
        {
            return $query->where('status', 'connected');
        }

        /**
         * Scope: Get peers by topology type
         */
        public function scopeByTopology(Builder $query, string $topology): Builder
        {
            return $query->where('connection_type', $topology);
        }

        /**
         * Scope: Get peers for a specific stream
         */
        public function scopeForStream(Builder $query, int $streamId): Builder
        {
            return $query->where('stream_id', $streamId);
        }

        /**
         * Get peer count for stream (for topology auto-switch)
         */
        public function scopeCountByStream(Builder $query, int $streamId): int
        {
            return $query->forStream($streamId)->connected()->count();
        }

        /**
         * Mark peer as connected
         */
        public function markConnected(): void
        {
            $this->update(['status' => 'connected']);
        }

        /**
         * Mark peer as failed
         */
        public function markFailed(string $reason = ''): void
        {
            $this->update([
                'status' => 'failed',
                'tags' => array_merge(
                    (array) $this->tags ?? [],
                    ['failed_reason' => $reason]
                ),
            ]);
        }

        /**
         * Close peer connection
         */
        public function close(): void
        {
            $this->update(['status' => 'closed']);
        }

        /**
         * Add ICE candidate to collection
         */
        public function addIceCandidate(array $candidate): void
        {
            $current = $this->ice_candidates ?? [];
            $current[] = $candidate;
            $this->update(['ice_candidates' => $current]);
        }

        /**
         * Switch topology (P2P → SFU)
         */
        public function switchToSFU(): void
        {
            $this->update(['connection_type' => 'sfu']);
        }

        /**
         * Factory
         */
        protected static function newFactory(): StreamPeerConnectionFactory
        {
            return StreamPeerConnectionFactory::new();
        }
}
