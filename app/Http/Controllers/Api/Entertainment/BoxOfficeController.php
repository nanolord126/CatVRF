<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Entertainment;
use App\Domains\EventPlanning\Entertainment\Services\TicketValidationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Entertainment\VerifyTicketRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * КАНОН 2026 — BUSINESS BOX OFFICE API CONTROLLER
 * 1. final class
 * 2. try/catch + correlation_id
 * 3. Middleware-protected (auth + business_owner)
 */
final class BoxOfficeController extends Controller
{
    public function __construct(
        private readonly TicketValidationService $validationService
    ) {
    }
    /**
     * Валидация билета на входе
     */
    public function verify(VerifyTicketRequest $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', (string) Str::uuid());
        try {
            $ticketId = $request->string('ticket_id')->toString();
            $isValid = $this->validationService->validateTicket(
                ticketUuid: $ticketId,
                correlationId: $correlationId
            );
            if ($isValid) {
                // Вход подтвержден (чек-ин)
                $this->validationService->checkIn($ticketId, $correlationId);
                return response()->json([
                    'success' => true,
                    'message' => 'TICKET VALIDATED & CHECKED IN',
                    'correlation_id' => $correlationId
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'INVALID OR ALREADY USED TICKET',
                'correlation_id' => $correlationId
            ], 403);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('BoxOffice verification fatal error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            return response()->json(['error' => 'Critical error during verification'], 500);
        }
    }
}
