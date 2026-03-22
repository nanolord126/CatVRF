<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Jobs\DeductConsumablesJob;
use App\Domains\Beauty\Models\Appointment;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class AppointmentService
{
    public function __construct(
        private FraudControlService $fraudControl,
        private WalletService $wallet,
    ) {}

    public function book(array $data): Appointment
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        $this->fraudControl->check([
            'operation' => 'book_appointment',
            'user_id' => $data['client_id'],
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($data, $correlationId): Appointment {
            $appointment = Appointment::create([
                ...$data,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            Log::channel('audit')->info('Appointment booked', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    public function complete(int $appointmentId, string $correlationId): Appointment
    {
        return DB::transaction(function () use ($appointmentId, $correlationId): Appointment {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointmentId);

            $appointment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DeductConsumablesJob::dispatch($appointmentId, $correlationId);

            if (isset($appointment->master->wallet_id)) {
                $this->wallet->credit(
                    $appointment->master->wallet_id,
                    $appointment->price,
                    'appointment_completed',
                    $correlationId
                );
            }

            event(new AppointmentCompleted($appointment, $correlationId));

            Log::channel('audit')->info('Appointment completed', [
                'appointment_id' => $appointmentId,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    public function cancel(int $appointmentId, string $reason, string $correlationId): Appointment
    {
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
