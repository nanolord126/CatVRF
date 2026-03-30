<?php declare(strict_types=1);

namespace App\Services\Legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsultationService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Constructor injection for required dependencies.
         */
        public function __construct(
            private readonly FraudControlService $fraudControl,
            private readonly PricingService $pricing,
        ) {}

        /**
         * Create a new legal consultation session with full audit.
         */
        public function bookConsultation(
            User $client,
            Lawyer $lawyer,
            \DateTime $scheduledAt,
            int $durationMinutes = 60,
            string $complexity = 'standard',
            bool $isUrgent = false,
            string $correlationId = null
        ): LegalConsultation {
            $correlationId = $correlationId ?? (string) Str::uuid();

            // 1. Fraud Check before booking
            $this->fraudControl->check($client->id, 'legal_consultation_book', [
                'lawyer_id' => $lawyer->id,
                'scheduled_at' => $scheduledAt,
                'correlation_id' => $correlationId,
            ]);

            // 2. Price calculation
            $priceInCents = $this->pricing->calculateConsultationPrice(
                $lawyer,
                $complexity,
                $isUrgent,
                false, // B2C by default here
                $correlationId
            );

            Log::channel('audit')->info('Attempting to book legal consultation', [
                'client_id' => $client->id,
                'lawyer_id' => $lawyer->id,
                'scheduled_at' => $scheduledAt,
                'correlation_id' => $correlationId,
            ]);

            // 3. Database transaction
            return DB::transaction(function () use ($client, $lawyer, $scheduledAt, $durationMinutes, $priceInCents, $correlationId) {
                // Lock lawyer for availability check (pessimistic locking)
                DB::table('lawyers')->where('id', $lawyer->id)->lockForUpdate()->first();

                // Create record
                $consultation = LegalConsultation::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => tenant()->id ?? 0,
                    'lawyer_id' => $lawyer->id,
                    'client_id' => $client->id,
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => $durationMinutes,
                    'price' => $priceInCents,
                    'status' => 'pending',
                    'type' => 'online',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Legal consultation booked successfully', [
                    'consultation_id' => $consultation->id,
                    'correlation_id' => $correlationId,
                ]);

                return $consultation;
            });
        }

        /**
         * Complete consultation and update summary.
         */
        public function completeConsultation(LegalConsultation $consultation, string $summary, string $correlationId = null): void
        {
            $correlationId = $correlationId ?? $consultation->correlation_id;

            Log::channel('audit')->info('Completing legal consultation', [
                'consultation_id' => $consultation->id,
                'correlation_id' => $correlationId,
            ]);

            DB::transaction(function () use ($consultation, $summary, $correlationId) {
                $consultation->update([
                    'status' => 'completed',
                    'summary' => $summary, // Ensure this is confidential/encrypted as per rule
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Legal consultation completed', [
                    'consultation_id' => $consultation->id,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Get available consultations for a client.
         */
        public function getClientConsultations(User $client): Collection
        {
            return LegalConsultation::where('client_id', $client->id)
                ->with(['lawyer', 'lawyer.firm'])
                ->orderByDesc('scheduled_at')
                ->get();
        }
}
