<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LegalApiController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Constructor injection for services.
         */
        public function __construct(
            private readonly ConsultationService $consultationService,
            private readonly ContractService $contractService,
            private readonly AILegalAdvisorConstructor $aiAdvisor,
        ) {}
        /**
         * Get AI Recommendations based on user requirements.
         * Endpoint: GET /api/v1/legal/recommendations
         */
        public function getRecommendations(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $request->validate([
                'case_type' => 'required|string|in:civil,criminal,corporate,notary,arbitration,family,real_estate,labor',
                'budget' => 'required|integer|min:100',
                'region' => 'nullable|string',
                'is_urgent' => 'nullable|boolean',
            ]);
            try {
                Log::channel('audit')->info('Legal API: AI recommendations requested', [
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);
                $result = $this->aiAdvisor->constructAdvisorRecommendation(
                    $request->get('case_type'),
                    (int) $request->get('budget'),
                    (bool) $request->get('is_urgent', false),
                    $request->get('region', 'Москва'),
                    $correlationId
                );
                return response()->json($result);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Legal API Error: AI Advisor failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка AI-конструктора.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Book a legal consultation.
         * Endpoint: POST /api/v1/legal/consultations
         */
        public function bookConsultation(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $request->validate([
                'lawyer_uuid' => 'required|uuid|exists:lawyers,uuid',
                'scheduled_at' => 'required|date|after:now',
                'duration_minutes' => 'nullable|integer|min:15|max:480',
                'complexity' => 'nullable|string|in:standard,high,special',
                'is_urgent' => 'nullable|boolean',
            ]);
            $lawyer = Lawyer::where('uuid', $request->get('lawyer_uuid'))->firstOrFail();
            try {
                $consultation = $this->consultationService->bookConsultation(
                    $request->user(),
                    $lawyer,
                    new \DateTime($request->get('scheduled_at')),
                    (int) $request->get('duration_minutes', 60),
                    $request->get('complexity', 'standard'),
                    (bool) $request->get('is_urgent', false),
                    $correlationId
                );
                return response()->json([
                    'success' => true,
                    'consultation_uuid' => $consultation->uuid,
                    'price_cents' => $consultation->price,
                    'status' => $consultation->status,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Legal API Error: Booking failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось забронировать консультацию.',
                    'error_type' => class_basename($e),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * Draft a legal contract / document.
         * Endpoint: POST /api/v1/legal/contracts
         */
        public function draftContract(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string|min:10',
                'consultation_uuid' => 'nullable|uuid|exists:legal_consultations,uuid',
            ]);
            $consultation = null;
            if ($request->has('consultation_uuid')) {
                $consultation = LegalConsultation::where('uuid', $request->get('consultation_uuid'))->first();
            }
            try {
                $contract = $this->contractService->draftContract(
                    $request->user(),
                    $request->get('title'),
                    $request->get('content'),
                    $consultation,
                    $correlationId
                );
                return response()->json([
                    'success' => true,
                    'contract_uuid' => $contract->uuid,
                    'status' => $contract->status,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Legal API Error: Drafting failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при подготовке черновика договора.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Get list of lawyers for a specific case type.
         * Endpoint: GET /api/v1/legal/lawyers
         */
        public function listLawyers(Request $request): JsonResponse
        {
            $request->validate([
                'category' => 'nullable|string',
                'city' => 'nullable|string',
            ]);
            $query = Lawyer::with(['firm'])->active();
            if ($request->has('category')) {
                $query->whereJsonContains('categories', $request->get('category'));
            }
            if ($request->has('city')) {
                $query->whereHas('firm', fn($q) => $q->where('city', $request->get('city')));
            }
            $lawyers = $query->paginate(15);
            return response()->json($lawyers);
        }
}
