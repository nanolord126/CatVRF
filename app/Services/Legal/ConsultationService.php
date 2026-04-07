<?php declare(strict_types=1);

namespace App\Services\Legal;


use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Services\Legal\PricingService;
use App\Models\User;
use App\Models\Lawyer;
use App\Models\Legal\LegalConsultation;
use Illuminate\Database\Eloquent\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class ConsultationService
{

    /**
         * Constructor injection for required dependencies.
         */
        public function __construct(
        private readonly Request $request,
            private readonly FraudControlService $fraud,
            private readonly PricingService $pricing,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
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
            $this->fraud->check((int) $client->id, 'legal_consultation_book', $this->request->ip());

            // 2. Price calculation
            $priceInCents = $this->pricing->calculateConsultationPrice(
                $lawyer,
                $complexity,
                $isUrgent,
                false, // B2C by default here
                $correlationId
            );

            $this->logger->channel('audit')->info('Attempting to book legal consultation', [
                'client_id' => $client->id,
                'lawyer_id' => $lawyer->id,
                'scheduled_at' => $scheduledAt,
                'correlation_id' => $correlationId,
            ]);

            // 3. Database transaction
            return $this->db->transaction(function () use ($client, $lawyer, $scheduledAt, $durationMinutes, $priceInCents, $correlationId) {
                // Lock lawyer for availability check (pessimistic locking)
                $this->db->table('lawyers')->where('id', $lawyer->id)->lockForUpdate()->first();

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

                $this->logger->channel('audit')->info('Legal consultation booked successfully', [
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

            $this->logger->channel('audit')->info('Completing legal consultation', [
                'consultation_id' => $consultation->id,
                'correlation_id' => $correlationId,
            ]);

            $this->db->transaction(function () use ($consultation, $summary, $correlationId) {
                $consultation->update([
                    'status' => 'completed',
                    'summary' => $summary, // Ensure this is confidential/encrypted as per rule
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->channel('audit')->info('Legal consultation completed', [
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
