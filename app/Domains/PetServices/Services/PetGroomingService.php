<?php declare(strict_types=1);

namespace App\Domains\PetServices\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\PetServices\Models\PetGroomingService as PetGroomingServiceModel;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PetGroomingService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function createGroomingAppointment(array $data): PetGroomingServiceModel
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createGroomingAppointment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createGroomingAppointment', ['domain' => __CLASS__]);

        Log::channel('audit')->info('PetGroomingService: Creating grooming appointment', [
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'pet_clinic_id' => $data['pet_clinic_id'],
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return DB::transaction(fn () => PetGroomingServiceModel::create([
            'uuid' => Str::uuid(),
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'tenant_id' => filament()->getTenant()->id,
            'pet_clinic_id' => $data['pet_clinic_id'],
            'pet_id' => $data['pet_id'],
            'groomer_id' => $data['groomer_id'],
            'service_type' => $data['service_type'] ?? 'basic',
            'appointment_date' => $data['appointment_date'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'price' => $data['price'] ?? 150000,
            'status' => 'pending',
            'tags' => $data['tags'] ?? [],
        ]));
    }

    public function confirmGroomingAppointment(int $appointmentId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'confirmGroomingAppointment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL confirmGroomingAppointment', ['domain' => __CLASS__]);

        $appointment = PetGroomingServiceModel::findOrFail($appointmentId);

        Log::channel('audit')->info('PetGroomingService: Confirming grooming appointment', [
            'correlation_id' => $appointment->correlation_id,
            'appointment_id' => $appointmentId,
        ]);

        return DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => 'confirmed']);
            return true;
        });
    }

    public function completeGroomingAppointment(int $appointmentId, array $photoUrls = []): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'completeGroomingAppointment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeGroomingAppointment', ['domain' => __CLASS__]);

        $appointment = PetGroomingServiceModel::findOrFail($appointmentId);

        Log::channel('audit')->info('PetGroomingService: Completing grooming appointment', [
            'correlation_id' => $appointment->correlation_id,
            'appointment_id' => $appointmentId,
        ]);

        return DB::transaction(function () use ($appointment, $photoUrls) {
            $appointment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'photo_urls' => $photoUrls,
            ]);

            // Credit to clinic wallet
            $this->walletService->credit(
                tenantId: $appointment->tenant_id,
                amount: (int) ($appointment->price * 0.86),
                reason: 'grooming_service_completed',
                correlationId: $appointment->correlation_id,
            );

            return true;
        });
    }

    public function getAvailableSlots(int $groomerIdId, string $date): Collection
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableSlots'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableSlots', ['domain' => __CLASS__]);

        $appointmentCount = PetGroomingServiceModel::where('groomer_id', $groomerIdId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->count();

        return collect(range(9, 17))->map(function (int $hour) use ($appointmentCount) {
            return [
                'hour' => $hour,
                'available' => $appointmentCount < 8,
            ];
        })->filter(fn ($slot) => $slot['available']);
    }

    public function cancelGroomingAppointment(int $appointmentId, string $reason = ''): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelGroomingAppointment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelGroomingAppointment', ['domain' => __CLASS__]);

        $appointment = PetGroomingServiceModel::findOrFail($appointmentId);

        Log::channel('audit')->info('PetGroomingService: Cancelling grooming appointment', [
            'correlation_id' => $appointment->correlation_id,
            'appointment_id' => $appointmentId,
            'reason' => $reason,
        ]);

        return DB::transaction(function () use ($appointment, $reason) {
            $appointment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            return true;
        });
    }
}
