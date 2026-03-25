<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\StreamPeerConnection;
use App\Services\MeshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class MeshController extends Controller
{
    public function __construct(
        private readonly MeshService $mesh,
    ) {}

    /**
     * Join stream - create peer connection
     */
    public function join(Request $request, Event $stream): JsonResponse
    {
        try {
            $validated = $request->validate([
                'peer_id' => 'required|string|max:255',
            ]);

            $correlationId = Str::uuid()->toString();

            $peerConnection = $this->mesh->joinRoom(
                $stream,
                auth()->user(),
                $validated['peer_id'],
                $correlationId
            );

            return response()->json([
                'status' => 'joined',
                'peer_id' => $validated['peer_id'],
                'room_id' => "stream.{$stream->id}",
                'correlation_id' => $correlationId,
                'turn_servers' => $this->getTurnServers(),
            ]);
        } catch (\Exception $e) {
            $this->log->channel('fraud_alert')->error('Mesh join failed', [
                'stream_id' => $stream->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to join stream',
            ], 500);
        }
    }

    /**
     * Send SDP offer
     */
    public function offer(Request $request, Event $stream): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_peer' => 'required|string|max:255',
                'to_peer' => 'required|string|max:255',
                'sdp' => 'required|string',
            ]);

            $correlationId = Str::uuid()->toString();

            $this->mesh->sendOffer(
                $stream,
                $validated['from_peer'],
                $validated['to_peer'],
                $validated['sdp'],
                $correlationId
            );

            return response()->json([
                'status' => 'sent',
                'type' => 'offer',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('fraud_alert')->error('Offer send failed', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Send SDP answer
     */
    public function answer(Request $request, Event $stream): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_peer' => 'required|string|max:255',
                'to_peer' => 'required|string|max:255',
                'sdp' => 'required|string',
            ]);

            $correlationId = Str::uuid()->toString();

            $this->mesh->sendAnswer(
                $stream,
                $validated['from_peer'],
                $validated['to_peer'],
                $validated['sdp'],
                $correlationId
            );

            return response()->json([
                'status' => 'sent',
                'type' => 'answer',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('fraud_alert')->error('Answer send failed', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add ICE candidate for NAT traversal
     */
    public function iceCandidate(Request $request, Event $stream): JsonResponse
    {
        try {
            $validated = $request->validate([
                'peer_id' => 'required|string|max:255',
                'candidate' => 'required|string',
                'sdp_mline_index' => 'required|integer|min:0',
                'sdp_mid' => 'nullable|string',
            ]);

            $correlationId = Str::uuid()->toString();

            $this->mesh->addIceCandidate(
                $stream,
                $validated['peer_id'],
                $validated['candidate'],
                $validated['sdp_mline_index'],
                $validated['sdp_mid'],
                $correlationId
            );

            return response()->json([
                'status' => 'added',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('fraud_alert')->error('ICE candidate add failed', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark peer as connected
     */
    public function connected(Request $request, Event $stream): JsonResponse
    {
        try {
            $validated = $request->validate([
                'peer_id' => 'required|string|max:255',
            ]);

            $this->mesh->markConnected($validated['peer_id']);

            // Check topology and auto-switch if needed
            $switched = $this->mesh->checkTopology($stream);

            return response()->json([
                'status' => 'connected',
                'topology_switched' => $switched,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('fraud_alert')->error('Mark connected failed', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark peer connection as failed
     */
    public function failed(Request $request, Event $stream): JsonResponse
    {
        try {
            $validated = $request->validate([
                'peer_id' => 'required|string|max:255',
                'reason' => 'nullable|string|max:500',
            ]);

            $this->mesh->markFailed(
                $validated['peer_id'],
                $validated['reason'] ?? ''
            );

            return response()->json(['status' => 'failed']);
        } catch (\Exception $e) {
            $this->log->channel('fraud_alert')->error('Mark failed failed', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get TURN servers config
     */
    private function getTurnServers(): array
    {
        return [
            [
                'urls' => config('services.webrtc.stun'),
                'protocol' => 'stun',
            ],
            [
                'urls' => config('services.webrtc.turn.url'),
                'username' => config('services.webrtc.turn.username'),
                'credential' => config('services.webrtc.turn.credential'),
                'protocol' => 'turn',
            ],
        ];
    }
}
