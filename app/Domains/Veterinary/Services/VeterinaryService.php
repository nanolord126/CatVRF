<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VeterinaryService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param FraudControlService $fraudControl
         * @param string $correlationId
         */
        public function __construct(
            private FraudControlService $fraudControl,
            private string $correlationId = ''
        ) {
        }

        /**
         * Get or set the current correlation ID
         */
        private function getCorrelationId(): string
        {
            return $this->correlationId ?: Str::uuid()->toString();
        }

        /**
         * Register a new pet with full validation and logging
         */
        public function registerPet(array $data, int $ownerId): Pet
        {
            $correlationId = $this->getCorrelationId();

            Log::channel('audit')->info('Attempting to register new pet', [
                'owner_id' => $ownerId,
                'name' => $data['name'] ?? 'Unknown',
                'correlation_id' => $correlationId
            ]);

            // 1. Policy/Fraud Check
            $this->fraudControl->check();

            return DB::transaction(function () use ($data, $ownerId, $correlationId) {
                $pet = Pet::create([
                    'tenant_id' => tenant()->id ?? 1,
                    'owner_id' => $ownerId,
                    'name' => $data['name'],
                    'species' => $data['species'],
                    'breed' => $data['breed'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'gender' => $data['gender'] ?? 'unknown',
                    'chip_number' => $data['chip_number'] ?? null,
                    'tags' => $data['tags'] ?? [],
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Pet successfully registered', [
                    'pet_id' => $pet->id,
                    'pet_uuid' => $pet->uuid,
                    'correlation_id' => $correlationId
                ]);

                return $pet;
            });
        }

        /**
         * Search available services in a clinic
         */
        public function getClinicServices(int $clinicId, string $category = null): Collection
        {
            $query = ServiceModel::where('clinic_id', $clinicId)->where('is_active', true);

            if ($category) {
                $query->where('category', $category);
            }

            return $query->get();
        }

        /**
         * Create a complex veterinary appointment
         * Mandatory: B2C vs B2B branch logic
         */
        public function createAppointment(array $data, bool $isB2B = false): VeterinaryAppointment
        {
            $correlationId = $this->getCorrelationId();

            Log::channel('audit')->info('Initiating veterinary appointment creation', [
                'client_id' => $data['client_id'],
                'clinic_id' => $data['clinic_id'],
                'is_b2b' => $isB2B,
                'correlation_id' => $correlationId
            ]);

            // 1. Mandatory Pre-check
            $this->fraudControl->check();

            return DB::transaction(function () use ($data, $isB2B, $correlationId) {

                // Logic: B2B usually allows bulk or credit line appointments
                // B2C requires immediate hold or prepayment (WalletService integration would be here)

                $appointment = VeterinaryAppointment::create([
                    'tenant_id' => tenant()->id ?? 1,
                    'clinic_id' => $data['clinic_id'],
                    'veterinarian_id' => $data['veterinarian_id'] ?? null,
                    'pet_id' => $data['pet_id'],
                    'service_id' => $data['service_id'],
                    'client_id' => $data['client_id'],
                    'appointment_at' => $data['appointment_at'],
                    'status' => 'pending',
                    'final_price' => $data['final_price'],
                    'payment_status' => 'unpaid',
                    'symptoms' => $data['symptoms'] ?? null,
                    'tags' => array_merge($data['tags'] ?? [], [
                        'source' => $isB2B ? 'b2b_portal' : 'b2c_marketplace',
                        'urgency' => $data['urgency'] ?? 'normal'
                    ]),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Veterinary appointment created', [
                    'appointment_id' => $appointment->id,
                    'appointment_uuid' => $appointment->uuid,
                    'correlation_id' => $correlationId
                ]);

                return $appointment;
            });
        }

        /**
         * Update clinic rating based on reviews
         */
        public function recalculateClinicRating(int $clinicId): void
        {
            $clinic = VeterinaryClinic::findOrFail($clinicId);

            $avg = DB::table('reviews')
                ->where('reviewable_type', VeterinaryClinic::class)
                ->where('reviewable_id', $clinicId)
                ->avg('rating') ?: 0;

            $count = DB::table('reviews')
                ->where('reviewable_type', VeterinaryClinic::class)
                ->where('reviewable_id', $clinicId)
                ->count();

            $clinic->update([
                'rating' => round((float)$avg, 1),
                'review_count' => $count
            ]);
        }
}
