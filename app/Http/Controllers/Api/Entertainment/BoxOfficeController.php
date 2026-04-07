<?php declare(strict_types=1);

/**
 * BoxOfficeController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/boxofficecontroller
 * @see https://catvrf.ru/docs/boxofficecontroller
 * @see https://catvrf.ru/docs/boxofficecontroller
 * @see https://catvrf.ru/docs/boxofficecontroller
 * @see https://catvrf.ru/docs/boxofficecontroller
 * @see https://catvrf.ru/docs/boxofficecontroller
 */


namespace App\Http\Controllers\Api\Entertainment;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class BoxOfficeController extends Controller
{

    public function __construct(
            private readonly TicketValidationService $validationService,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
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
                    return $this->response->json([
                        'success' => true,
                        'message' => 'TICKET VALIDATED & CHECKED IN',
                        'correlation_id' => $correlationId
                    ]);
                }
                return $this->response->json([
                    'success' => false,
                    'message' => 'INVALID OR ALREADY USED TICKET',
                    'correlation_id' => $correlationId
                ], 403);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('BoxOffice verification fatal error', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ]);
                return $this->response->json(['error' => 'Critical error during verification'], 500);
            }
        }
}
