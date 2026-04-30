<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services\AI;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

/**
 * InstantCheckInService - Handles NFC/QR code instant check-in
 * 
 * Allows patients to check in for appointments using NFC or QR codes.
 */
final readonly class InstantCheckInService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Check in patient using NFC or QR code
     */
    public function checkIn(int $appointmentId, string $nfcData = '', string $qrCode = '', string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        $appointment = $this->db->table('medical_appointments')
            ->where('id', $appointmentId)
            ->first();

        if ($appointment === null) {
            throw new \RuntimeException('Appointment not found');
        }

        if ($appointment->status !== 'confirmed') {
            throw new \RuntimeException('Appointment must be confirmed to check in');
        }

        if ($appointment->check_in_time !== null) {
            throw new \RuntimeException('Already checked in');
        }

        // Verify NFC or QR code
        if ($nfcData !== '' && !$this->verifyNFC($nfcData, $appointment)) {
            throw new \RuntimeException('Invalid NFC data');
        }

        if ($qrCode !== '' && !$this->verifyQR($qrCode, $appointment)) {
            throw new \RuntimeException('Invalid QR code');
        }

        // Update appointment with check-in time
        $this->db->table('medical_appointments')
            ->where('id', $appointmentId)
            ->update([
                'status' => 'checked_in',
                'check_in_time' => CarbonImmutable::now(),
                'check_in_method' => $nfcData !== '' ? 'nfc' : 'qr',
            ]);

        Log::info('instant_check_in.completed', [
            'appointment_id' => $appointmentId,
            'user_id' => $appointment->user_id,
            'method' => $nfcData !== '' ? 'nfc' : 'qr',
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'appointment_id' => $appointmentId,
            'check_in_time' => CarbonImmutable::now()->toIso8601String(),
            'queue_position' => $this->getQueuePosition($appointment->doctor_id, $appointmentId),
        ];
    }

    /**
     * Verify NFC data
     */
    private function verifyNFC(string $nfcData, object $appointment): bool
    {
        // Implement NFC verification logic
        // For now, simple hash check
        $expectedHash = hash('sha256', $appointment->uuid . config('app.key'));
        return hash_equals(substr($expectedHash, 0, 16), $nfcData);
    }

    /**
     * Verify QR code
     */
    private function verifyQR(string $qrCode, object $appointment): bool
    {
        // Implement QR verification logic
        // QR code should contain appointment UUID
        return hash_equals($appointment->uuid, $qrCode);
    }

    /**
     * Get queue position for the patient
     */
    private function getQueuePosition(int $doctorId, int $appointmentId): int
    {
        return $this->db->table('medical_appointments')
            ->where('doctor_id', $doctorId)
            ->where('status', 'checked_in')
            ->where('check_in_time', '<=', function ($query) {
                $query->select('check_in_time')
                    ->from('medical_appointments')
                    ->where('id', $this->db->raw('medical_appointments.id'));
            })
            ->count();
    }
}
