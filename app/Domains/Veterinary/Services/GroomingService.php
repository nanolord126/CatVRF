<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GroomingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraudControl,
            private string $correlationId = ''
        ) {
        }

        private function getCorrelationId(): string
        {
            return $this->correlationId ?: Str::uuid()->toString();
        }

        /**
         * Book a regular grooming session
         */
        public function bookSession(int $petId, int $clinicId, \DateTimeInterface $at, int $price): VeterinaryAppointment
        {
            $correlationId = $this->getCorrelationId();

            Log::channel('audit')->info('GroomingService: Booking grooming session', [
                'pet_id' => $petId,
                'clinic_id' => $clinicId,
                'at' => $at->format('Y-m-d H:i:s'),
                'correlation_id' => $correlationId
            ]);

            $this->fraudControl->check();

            return DB::transaction(function () use ($petId, $clinicId, $at, $price, $correlationId) {

                // Auto find grooming service for this clinic
                $service = \App\Domains\Veterinary\Models\VeterinaryService::where('clinic_id', $clinicId)
                    ->where('category', 'grooming')
                    ->where('is_active', true)
                    ->firstOrFail();

                $pet = Pet::findOrFail($petId);

                $appointment = VeterinaryAppointment::create([
                    'tenant_id' => tenant()->id ?? 1,
                    'clinic_id' => $clinicId,
                    'pet_id' => $petId,
                    'service_id' => $service->id,
                    'client_id' => $pet->owner_id,
                    'appointment_at' => $at,
                    'status' => 'confirmed', // Grooming can be auto-confirmed if available
                    'final_price' => $price,
                    'payment_status' => 'unpaid',
                    'tags' => ['type' => 'grooming'],
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('GroomingService: Session booked', [
                    'id' => $appointment->id,
                    'correlation_id' => $correlationId
                ]);

                return $appointment;
            });
        }

        /**
         * Complete grooming and update pet tags with hygiene data
         */
        public function completeAndTag(int $appointmentId): void
        {
            $correlationId = $this->getCorrelationId();

            DB::transaction(function () use ($appointmentId, $correlationId) {
                $appointment = VeterinaryAppointment::findOrFail($appointmentId);
                $appointment->update([
                    'status' => 'completed',
                    'correlation_id' => $correlationId
                ]);

                $pet = $appointment->pet;
                $tags = $pet->tags ?? [];
                $tags['last_grooming'] = $appointment->appointment_at->toIso8601String();
                $tags['hygiene_rating'] = ($tags['hygiene_rating'] ?? 0) + 1;

                $pet->update([
                    'tags' => $tags,
                    'correlation_id' => $correlationId
                ]);

                Log::channel('audit')->info('GroomingService: Session completed and pet tagged', [
                    'appointment_id' => $appointmentId,
                    'pet_id' => $pet->id,
                    'correlation_id' => $correlationId
                ]);
            });
        }
}
