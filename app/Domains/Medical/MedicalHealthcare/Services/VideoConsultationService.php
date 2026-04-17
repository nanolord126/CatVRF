<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services;

use App\Domains\Medical\Models\MedicalAppointment;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final class VideoConsultationService
{
    public function __construct(
        private FraudControlService $fraud,
    ) {
    }

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

        Redis::setex(
            "healthcare:webrtc:token:{$token}",
            $expiresAt->diffInSeconds(now()),
            json_encode([
                'appointment_id' => $appointmentId,
                'user_id' => $appointment->user_id,
                'doctor_id' => $appointment->doctor_id,
                'room_name' => $roomName,
                'correlation_id' => $correlationId,
            ])
        );

        Log::channel('audit')->info('Video consultation token generated', [
            'appointment_id' => $appointmentId,
            'correlation_id' => $correlationId,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return [
            'token' => $token,
            'room_name' => $roomName,
            'webrtc_url' => config('services.webrtc.endpoint') . "/room/{$roomName}?token={$token}",
            'expires_at' => $expiresAt->toIso8601String(),
            'doctor_name' => $appointment->doctor->name,
        ];
    }
}
