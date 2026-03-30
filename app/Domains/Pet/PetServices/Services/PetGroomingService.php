<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetGroomingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
            private readonly WalletService $walletService,
        ) {}

        public function createGroomingAppointment(array $data): PetGroomingServiceModel
        {
            Log::channel('audit')->info('PetGroomingService: Creating grooming appointment', [
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'pet_clinic_id' => $data['pet_clinic_id'],
                'tenant_id' => filament()->getTenant()->id,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(fn () => PetGroomingServiceModel::create([
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
            $appointment = PetGroomingServiceModel::findOrFail($appointmentId);

            Log::channel('audit')->info('PetGroomingService: Confirming grooming appointment', [
                'correlation_id' => $appointment->correlation_id,
                'appointment_id' => $appointmentId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($appointment) {
                $appointment->update(['status' => 'confirmed']);
                return true;
            });
        }

        public function completeGroomingAppointment(int $appointmentId, array $photoUrls = []): bool
        {
            $appointment = PetGroomingServiceModel::findOrFail($appointmentId);

            Log::channel('audit')->info('PetGroomingService: Completing grooming appointment', [
                'correlation_id' => $appointment->correlation_id,
                'appointment_id' => $appointmentId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($appointment, $photoUrls) {
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
            $appointment = PetGroomingServiceModel::findOrFail($appointmentId);

            Log::channel('audit')->info('PetGroomingService: Cancelling grooming appointment', [
                'correlation_id' => $appointment->correlation_id,
                'appointment_id' => $appointmentId,
                'reason' => $reason,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($appointment, $reason) {
                $appointment->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now(),
                ]);

                return true;
            });
        }
}
