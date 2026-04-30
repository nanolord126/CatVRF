<?php

declare(strict_types=1);

namespace Modules\Video\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Log;
use Modules\Video\Domain\Exceptions\VideoRoomException;
use Modules\Video\Domain\ValueObjects\ParticipantRole;
use Modules\Video\Domain\ValueObjects\RoomType;

final readonly class LiveKitService
{
    use WithAuditLogging;

    private const ACCESS_TOKEN_TTL = 3600; // 1 hour

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function createRoom(string $roomName, RoomType $type): void
    {
        $apiKey = config('video.livekit.api_key');
        $apiSecret = config('video.livekit.api_secret');
        $hostUrl = config('video.livekit.host_url');

        if (empty($apiKey) || empty($apiSecret) || empty($hostUrl)) {
            $this->logAction('livekit_credentials_not_configured', 'VideoRoom', null, [
                'message' => 'Skipping room creation due to missing credentials',
            ], null, null);
            return;
        }

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post("{$hostUrl}/twirp/livekit.RoomService/CreateRoom", [
                'headers' => [
                    'Authorization' => $this->generateApiKeyAuth($apiKey, $apiSecret),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'name' => $roomName,
                    'emptyTimeout' => 300, // 5 minutes
                    'maxParticipants' => $type->getMaxParticipants(),
                    'enableRecording' => $type->requiresRecordingConsent(),
                ],
                'timeout' => 10,
            ]);

            $this->logAction('livekit_room_created', 'VideoRoom', null, [
                'room_name' => $roomName,
                'room_type' => $type->value,
            ], null, null);
        } catch (\Exception $e) {
            $this->logAction('livekit_room_creation_failed', 'VideoRoom', null, [
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ], null, null);
            throw VideoRoomException::livekitConnectionFailed($e->getMessage());
        }
    }

    public function deleteRoom(string $roomName): void
    {
        $apiKey = config('video.livekit.api_key');
        $apiSecret = config('video.livekit.api_secret');
        $hostUrl = config('video.livekit.host_url');

        if (empty($apiKey) || empty($apiSecret) || empty($hostUrl)) {
            return;
        }

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post("{$hostUrl}/twirp/livekit.RoomService/DeleteRoom", [
                'headers' => [
                    'Authorization' => $this->generateApiKeyAuth($apiKey, $apiSecret),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'room' => $roomName,
                ],
                'timeout' => 10,
            ]);

            $this->logAction('livekit_room_deleted', 'VideoRoom', null, [
                'room_name' => $roomName,
            ], null, null);
        } catch (\Exception $e) {
            $this->logAction('livekit_room_deletion_failed', 'VideoRoom', null, [
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ], null, null);
        }
    }

    public function generateAccessToken(string $roomName, string $identity, ParticipantRole $role): string
    {
        $apiKey = config('video.livekit.api_key');
        $apiSecret = config('video.livekit.api_secret');

        if (empty($apiKey) || empty($apiSecret)) {
            throw VideoRoomException::livekitConnectionFailed('Credentials not configured');
        }

        // JWT token generation for LiveKit
        $header = json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]);

        $now = time();
        $payload = json_encode([
            'sub' => $identity,
            'iss' => $apiKey,
            'nbf' => $now,
            'exp' => $now + self::ACCESS_TOKEN_TTL,
            'video' => [
                'room' => $roomName,
                'roomJoin' => true,
                'canPublish' => $role->canBroadcast(),
                'canSubscribe' => true,
                'canPublishData' => true,
                'canUpdateOwnMetadata' => true,
            ],
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $apiSecret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    private function generateApiKeyAuth(string $apiKey, string $apiSecret): string
    {
        // Simple API key auth for LiveKit
        return "Bearer {$apiKey}";
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function getRoomParticipants(string $roomName): array
    {
        $apiKey = config('video.livekit.api_key');
        $apiSecret = config('video.livekit.api_secret');
        $hostUrl = config('video.livekit.host_url');

        if (empty($apiKey) || empty($apiSecret) || empty($hostUrl)) {
            return [];
        }

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post("{$hostUrl}/twirp/livekit.RoomService/ListParticipants", [
                'headers' => [
                    'Authorization' => $this->generateApiKeyAuth($apiKey, $apiSecret),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'room' => $roomName,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['participants'] ?? [];
        } catch (\Exception $e) {
            $this->logAction('livekit_participants_list_failed', 'VideoRoom', null, [
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ], null, null);
            return [];
        }
    }

    public function startRecording(string $roomName): string
    {
        $apiKey = config('video.livekit.api_key');
        $apiSecret = config('video.livekit.api_secret');
        $hostUrl = config('video.livekit.host_url');

        if (empty($apiKey) || empty($apiSecret) || empty($hostUrl)) {
            throw VideoRoomException::livekitConnectionFailed('Credentials not configured');
        }

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post("{$hostUrl}/twirp/livekit.Egress/StartRoomCompositeEgress", [
                'headers' => [
                    'Authorization' => $this->generateApiKeyAuth($apiKey, $apiSecret),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'roomName' => $roomName,
                    'output' => [
                        'file' => [
                            'fileType' => 'MP4',
                            'filepath' => "recordings/{$roomName}_" . time() . '.mp4',
                        ],
                    ],
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $egressId = $data['egressId'] ?? null;

            if ($egressId === null) {
                throw VideoRoomException::livekitConnectionFailed('Failed to start recording');
            }

            $this->logAction('livekit_recording_started', 'VideoRecording', $egressId, [
                'room_name' => $roomName,
                'egress_id' => $egressId,
            ], null, null);

            return $egressId;
        } catch (\Exception $e) {
            $this->logAction('livekit_recording_start_failed', 'VideoRecording', null, [
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ], null, null);
            throw VideoRoomException::livekitConnectionFailed($e->getMessage());
        }
    }

    public function stopRecording(string $egressId): void
    {
        $apiKey = config('video.livekit.api_key');
        $apiSecret = config('video.livekit.api_secret');
        $hostUrl = config('video.livekit.host_url');

        if (empty($apiKey) || empty($apiSecret) || empty($hostUrl)) {
            return;
        }

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post("{$hostUrl}/twirp/livekit.Egress/StopEgress", [
                'headers' => [
                    'Authorization' => $this->generateApiKeyAuth($apiKey, $apiSecret),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'egressId' => $egressId,
                ],
                'timeout' => 10,
            ]);

            $this->logAction('livekit_recording_stopped', 'VideoRecording', $egressId, [
                'egress_id' => $egressId,
            ], null, null);
        } catch (\Exception $e) {
            $this->logAction('livekit_recording_stop_failed', 'VideoRecording', $egressId, [
                'error' => $e->getMessage(),
            ], null, null);
        }
    }
}
