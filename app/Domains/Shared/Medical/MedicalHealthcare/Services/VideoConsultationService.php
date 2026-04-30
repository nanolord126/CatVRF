<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\MedicalHealthcare\Services;

use Psr\Log\LoggerInterface;

use App\Domains\Shared\Medical\Models\MedicalAppointment;
use App\Services\FraudControlService;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class VideoConsultationService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}

    public function generateToken(int $appointmentId, int $userId, string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'video_consultation',
            amount: 0,
            correlationId: $correlationId,
        );

        $appointment = MedicalAppointment::with(['doctor', 'user'])->findOrFail($appointmentId);

        if ($appointment->consultation_type !== 'video') {
            throw new \RuntimeException('Эта консультация не является видео-консультацией.');
        }

        if ($appointment->status !== 'confirmed') {
            throw new \RuntimeException('Консультация не подтверждена.');
        }

        $token = Str::random(64);
        $roomName = "healthcare_consult_{$appointment->id}";
        $expiresAt = $appointment->appointment_datetime->addHours(2);

        $this->redis->connection()->setex(
            "healthcare:webrtc:token:{$token}",
            $expiresAt->diffInSeconds(CarbonImmutable::now()),
            json_encode([
                'appointment_id' => $appointmentId,
                'user_id' => $appointment->user_id,
                'doctor_id' => $appointment->doctor_id,
                'room_name' => $roomName,
                'correlation_id' => $correlationId,
            ])
        );

        $this->log->channel('audit')->$this->logger->info('Video consultation token generated', [
            'appointment_id' => $appointmentId,
            'correlation_id' => $correlationId,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return [
            'token' => $token,
            'room_name' => $roomName,
            'webrtc_url' => config('services.webrtc.endpoint')."/room/{$roomName}?token={$token}",
            'expires_at' => $expiresAt->toIso8601String(),
            'doctor_name' => $appointment->doctor->name,
        ];
    }
}
