<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautySalon;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class BeautyService
{
    public function __construct(
        private FraudControlService $fraudControl,
    ) {}

    public function createAppointment(array $data, bool $isB2B = false): Appointment
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        $this->fraudControl->check([
            'operation' => 'create_appointment',
            'user_id' => $data['client_id'] ?? null,
            'tenant_id' => $data['tenant_id'],
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($data, $isB2B, $correlationId): Appointment {
            // B2B logic: different pricing/commission
            if ($isB2B && isset($data['inn'], $data['business_card_id'])) {
                $data['commission_rate'] = 0.12; // 12% for B2B
                $data['business_group_id'] = $data['business_card_id'];
            } else {
                $data['commission_rate'] = 0.14; // 14% for B2C
            }

            $appointment = Appointment::create([
                ...$data,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
            ]);

            Log::channel('audit')->info('Appointment created', [
                'appointment_id' => $appointment->id,
                'is_b2b' => $isB2B,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    public function getSalons(int $tenantId, ?int $businessCardId = null): \Illuminate\Support\Collection
    {
        $query = BeautySalon::where('tenant_id', $tenantId);

        // B2B filter
        if ($businessCardId) {
            $query->where('business_group_id', $businessCardId);
        }

        return $query->where('status', 'active')->get();
    }

    public function confirmAppointment(int $appointmentId, string $correlationId): Appointment
    {
        return DB::transaction(function () use ($appointmentId, $correlationId): Appointment {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);
            $appointment->update(['status' => 'confirmed']);

            Log::channel('audit')->info('Appointment confirmed', [
                'appointment_id' => $appointmentId,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    public function completeAppointment(int $appointmentId, string $correlationId): Appointment
    {
        return DB::transaction(function () use ($appointmentId, $correlationId): Appointment {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);
            $appointment->update([
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            Log::channel('audit')->info('Appointment completed', [
                'appointment_id' => $appointmentId,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    public function cancelAppointment(
        int $appointmentId,
        string $reason,
        string $correlationId
    ): Appointment {
        return DB::transaction(function () use ($appointmentId, $reason, $correlationId): Appointment {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);
            $appointment->update(['status' => 'cancelled']);

            Log::channel('audit')->info('Appointment cancelled', [
                'appointment_id' => $appointmentId,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }
}
