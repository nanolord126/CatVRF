<?php declare(strict_types=1);

namespace App\Http\Controllers;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class MeshController extends Controller
{

    public function __construct(
        private readonly ConfigRepository $config,
            private readonly MeshService $mesh,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
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
                    $this->guard->user(),
                    $validated['peer_id'],
                    $correlationId
                );
                return $this->response->json([
                    'status' => 'joined',
                    'peer_id' => $validated['peer_id'],
                    'room_id' => "stream.{$stream->id}",
                    'correlation_id' => $correlationId,
                    'turn_servers' => $this->getTurnServers(),
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('fraud_alert')->error('Mesh join failed', [
                    'stream_id' => $stream->id,
                    'user_id' => $this->guard->id(),
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
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
                return $this->response->json([
                    'status' => 'sent',
                    'type' => 'offer',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('fraud_alert')->error('Offer send failed', [
                    'stream_id' => $stream->id,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
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
                return $this->response->json([
                    'status' => 'sent',
                    'type' => 'answer',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('fraud_alert')->error('Answer send failed', [
                    'stream_id' => $stream->id,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
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
                return $this->response->json([
                    'status' => 'added',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('fraud_alert')->error('ICE candidate add failed', [
                    'stream_id' => $stream->id,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
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
                return $this->response->json([
                    'status' => 'connected',
                    'topology_switched' => $switched,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('fraud_alert')->error('Mark connected failed', [
                    'stream_id' => $stream->id,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
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
                return $this->response->json(['status' => 'failed']);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('fraud_alert')->error('Mark failed failed', [
                    'stream_id' => $stream->id,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
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
                    'urls' => $this->config->get('services.webrtc.stun'),
                    'protocol' => 'stun',
                ],
                [
                    'urls' => $this->config->get('services.webrtc.turn.url'),
                    'username' => $this->config->get('services.webrtc.turn.username'),
                    'credential' => $this->config->get('services.webrtc.turn.credential'),
                    'protocol' => 'turn',
                ],
            ];
        }
}
