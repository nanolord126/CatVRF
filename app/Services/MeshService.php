<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Stream\AnswerSent;
use App\Events\Stream\IceCandidateSent;
use App\Events\Stream\OfferSent;
use App\Events\Stream\PeerJoined;
use App\Models\Event;
use App\Models\StreamPeerConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class MeshService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Create broadcast room for a stream
     * Returns room ID for frontend use
     */
    public function createRoom(Event $stream): string
    {
        if ($stream->tenant_id === null) {
            throw new InvalidArgumentException('Stream must have tenant_id');
        }

        $roomId = "stream.{$stream->id}";
        
        $this->log->channel('audit')->info('WebRTC room created', [
            'stream_id' => $stream->id,
            'tenant_id' => $stream->tenant_id,
            'room_id' => $roomId,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        return $roomId;
    }

    /**
     * Peer joins the stream - create peer connection record
     */
    public function joinRoom(
        Event $stream,
        User $user,
        string $peerId,
        ?string $correlationId = null
    ): StreamPeerConnection {
        $correlationId ??= Str::uuid()->toString();

        // Fraud check
        $this->fraudControl->check([
            'type' => 'stream_join',
            'user_id' => $user->id,
            'stream_id' => $stream->id,
            'peer_id' => $peerId,
        ], $correlationId);

        // Create peer connection record with transaction
        $peerConnection = $this->db->transaction(function () use (
            $stream,
            $user,
            $peerId,
            $correlationId
        ): StreamPeerConnection {
            return StreamPeerConnection::create([
                'stream_id' => $stream->id,
                'tenant_id' => $stream->tenant_id,
                'user_id' => $user->id,
                'peer_id' => $peerId,
                'status' => 'connecting',
                'connection_type' => 'p2p',
                'correlation_id' => $correlationId,
                'tags' => [
                    'ip' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ],
            ]);
        });

        // Broadcast peer joined event
        broadcast(new PeerJoined($stream->id, $peerId, $user->name ?? 'Guest'))
            ->toOthers();

        $this->log->channel('audit')->info('Peer joined stream', [
            'peer_id' => $peerId,
            'stream_id' => $stream->id,
            'user_id' => $user->id,
            'tenant_id' => $stream->tenant_id,
            'correlation_id' => $correlationId,
        ]);

        return $peerConnection;
    }

    /**
     * Send SDP offer from initiator to receiver
     */
    public function sendOffer(
        Event $stream,
        string $fromPeerId,
        string $toPeerId,
        string $sdp,
        ?string $correlationId = null
    ): void {
        $correlationId ??= Str::uuid()->toString();

        // Validate peers exist
        $this->validatePeers($stream, [$fromPeerId, $toPeerId], $correlationId);

        // Broadcast offer event
        broadcast(new OfferSent($stream->id, $fromPeerId, $toPeerId, $sdp))
            ->toOthers();

        $this->log->channel('audit')->debug('WebRTC offer sent', [
            'from' => $fromPeerId,
            'to' => $toPeerId,
            'stream_id' => $stream->id,
            'sdp_length' => strlen($sdp),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Send SDP answer from receiver to initiator
     */
    public function sendAnswer(
        Event $stream,
        string $fromPeerId,
        string $toPeerId,
        string $sdp,
        ?string $correlationId = null
    ): void {
        $correlationId ??= Str::uuid()->toString();

        // Validate peers
        $this->validatePeers($stream, [$fromPeerId, $toPeerId], $correlationId);

        // Store answer in peer connection
        $this->db->transaction(function () use ($fromPeerId, $sdp): void {
            StreamPeerConnection::where('peer_id', $fromPeerId)
                ->update(['remote_sdp' => $sdp]);
        });

        // Broadcast answer event
        broadcast(new AnswerSent($stream->id, $fromPeerId, $toPeerId, $sdp))
            ->toOthers();

        $this->log->channel('audit')->debug('WebRTC answer sent', [
            'from' => $fromPeerId,
            'to' => $toPeerId,
            'stream_id' => $stream->id,
            'sdp_length' => strlen($sdp),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Add ICE candidate for NAT traversal
     */
    public function addIceCandidate(
        Event $stream,
        string $peerId,
        string $candidate,
        int $sdpMLineIndex,
        ?string $sdpMid = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= Str::uuid()->toString();

        // Find peer connection and add candidate
        $peerConnection = $this->db->transaction(function () use (
            $peerId,
            $candidate,
            $sdpMLineIndex,
            $sdpMid
        ): ?StreamPeerConnection {
            $peer = StreamPeerConnection::where('peer_id', $peerId)->first();
            
            if ($peer) {
                $peer->addIceCandidate([
                    'candidate' => $candidate,
                    'sdpMLineIndex' => $sdpMLineIndex,
                    'sdpMid' => $sdpMid,
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            return $peer;
        });

        if (!$peerConnection) {
            $this->log->channel('fraud_alert')->warning('ICE candidate received for non-existent peer', [
                'peer_id' => $peerId,
                'stream_id' => $stream->id,
                'correlation_id' => $correlationId,
            ]);

            return;
        }

        // Find target peers in same stream and broadcast
        $streamPeers = StreamPeerConnection::forStream($stream->id)
            ->where('peer_id', '!=', $peerId)
            ->get();

        foreach ($streamPeers as $targetPeer) {
            broadcast(
                new IceCandidateSent(
                    $stream->id,
                    $peerId,
                    $targetPeer->peer_id,
                    $candidate,
                    $sdpMLineIndex,
                    $sdpMid
                )
            )->toOthers();
        }

        $this->log->channel('audit')->debug('ICE candidate added', [
            'peer_id' => $peerId,
            'stream_id' => $stream->id,
            'candidates_count' => count($peerConnection->ice_candidates ?? []),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Mark peer as successfully connected
     */
    public function markConnected(string $peerId): void
    {
        $this->db->transaction(function () use ($peerId): void {
            $peer = StreamPeerConnection::where('peer_id', $peerId)->first();
            if ($peer) {
                $peer->markConnected();
            }
        });

        $this->log->channel('audit')->info('Peer connection established', [
            'peer_id' => $peerId,
        ]);
    }

    /**
     * Mark peer connection as failed
     */
    public function markFailed(string $peerId, string $reason = ''): void
    {
        $this->db->transaction(function () use ($peerId, $reason): void {
            $peer = StreamPeerConnection::where('peer_id', $peerId)->first();
            if ($peer) {
                $peer->markFailed($reason);
            }
        });

        $this->log->channel('fraud_alert')->warning('Peer connection failed', [
            'peer_id' => $peerId,
            'reason' => $reason,
        ]);
    }

    /**
     * Auto-switch topology from P2P to SFU when peer count > threshold
     * Returns true if topology was switched
     */
    public function checkTopology(Event $stream, int $threshold = 15): bool
    {
        $connectedCount = StreamPeerConnection::forStream($stream->id)
            ->connected()
            ->count();

        if ($connectedCount > $threshold) {
            // Switch to SFU topology
            $this->db->transaction(function () use ($stream): void {
                $stream->update(['topology' => 'sfu']);
                
                // Update all peers to SFU
                StreamPeerConnection::forStream($stream->id)
                    ->update(['connection_type' => 'sfu']);
            });

            $this->log->channel('audit')->warning('Topology switched to SFU', [
                'stream_id' => $stream->id,
                'peer_count' => $connectedCount,
                'threshold' => $threshold,
                'correlation_id' => Str::uuid()->toString(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get all peers for a stream
     */
    public function getStreamPeers(Event $stream): Collection
    {
        return StreamPeerConnection::forStream($stream->id)
            ->with(['user'])
            ->get();
    }

    /**
     * Get peer connection details
     */
    public function getPeerConnection(string $peerId): ?StreamPeerConnection
    {
        return StreamPeerConnection::where('peer_id', $peerId)->first();
    }

    /**
     * Clean up closed connections (cleanup job)
     */
    public function cleanupClosedConnections(int $olderThanMinutes = 60): int
    {
        return $this->db->transaction(function () use ($olderThanMinutes): int {
            $cutoffTime = now()->subMinutes($olderThanMinutes);

            $deleted = StreamPeerConnection::where('status', 'closed')
                ->where('updated_at', '<', $cutoffTime)
                ->delete();

            if ($deleted > 0) {
                $this->log->channel('audit')->info('Cleaned up closed peer connections', [
                    'count' => $deleted,
                    'older_than_minutes' => $olderThanMinutes,
                ]);
            }

            return $deleted;
        });
    }

    /**
     * Validate that peers exist in stream
     */
    private function validatePeers(Event $stream, array $peerIds, string $correlationId): void
    {
        $existingPeers = StreamPeerConnection::forStream($stream->id)
            ->whereIn('peer_id', $peerIds)
            ->pluck('peer_id')
            ->toArray();

        $missingPeers = array_diff($peerIds, $existingPeers);

        if (!empty($missingPeers)) {
            $this->log->channel('fraud_alert')->warning('Invalid peer IDs in SDP exchange', [
                'stream_id' => $stream->id,
                'missing_peers' => $missingPeers,
                'correlation_id' => $correlationId,
            ]);

            throw new InvalidArgumentException(
                'One or more peer IDs do not exist in this stream'
            );
        }
    }
}
