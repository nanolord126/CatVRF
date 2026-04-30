<?php declare(strict_types=1);

namespace App\Domains\Shared\Medical\MedicalHealthcare\Services\AI;

use Illuminate\Database\DatabaseManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * VideoConsultationService - Manages video consultation tokens
 * 
 * Generates and manages WebRTC tokens for video consultations.
 */
final readonly class VideoConsultationService
{
    public function __construct(
        private readonly RedisConnection $redis,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Generate video consultation token for an appointment
     */
    public function generateToken(int $appointmentId, int $userId, string $correlationId): array
    {
        $appointment = $this->db->table('medical_appointments')
            ->where('id', $appointmentId)
            ->where('user_id', $userId)
            ->first();

        if ($appointment === null) {
            throw new \RuntimeException('Appointment not found');
        }

        $roomName = 'consultation_' . $appointment->uuid;
        $token = hash('sha256', $roomName . $correlationId . CarbonImmutable::now()->timestamp);
        $expiresAt = CarbonImmutable::now()->addMinutes(15);

        // Store token in Redis with expiration
        $tokenKey = "video:token:{$appointmentId}";
        $this->redis->setex($tokenKey, $expiresAt->diffInSeconds(CarbonImmutable::now()), json_encode([
            'token' => $token,
            'room_name' => $roomName,
            'user_id' => $userId,
            'appointment_id' => $appointmentId,
            'created_at' => CarbonImmutable::now()->toIso8601String(),
        ]));

        Log::info('video_consultation.token_generated', [
            'appointment_id' => $appointmentId,
            'correlation_id' => $correlationId,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return [
            'token' => $token,
            'room_name' => $roomName,
            'webrtc_url' => config('services.webrtc.endpoint') . "/room/{$roomName}?token={$token}",
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Validate video consultation token
     */
    public function validateToken(string $token, int $appointmentId): bool
    {
        $tokenKey = "video:token:{$appointmentId}";
        $storedData = $this->redis->get($tokenKey);

        if ($storedData === null) {
            return false;
        }

        $data = json_decode($storedData, true);
        return hash_equals($data['token'] ?? '', $token);
    }
}
