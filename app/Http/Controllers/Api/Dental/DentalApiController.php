<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Dental;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class DentalApiController extends Controller
{

    private readonly string $correlationId;
        public function __construct(
        private readonly Request $request,
            private readonly DentalClinicService $clinicService,
            private readonly DentalAppointmentService $appointmentService,
            private readonly TreatmentPlanService $planService,
            private readonly DentalSmileConstructorService $smileService,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    ) {
            $this->correlationId = $this->request->header('X-Correlation-ID', Str::uuid()->toString());
        }
        /**
         * Получить список клиник в текущем тенанте
         */
        public function getClinics(Request $request): JsonResponse
        {
            try {
                $clinics = $this->clinicService->getNearbyClinics(
                    (float) $request->get('lat', 55.7558),
                    (float) $request->get('lon', 37.6173),
                    (int) $request->get('radius', 10)
                );
                return $this->response->json([
                    'success' => true,
                    'data' => $clinics,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logError('getClinics', $e);
                return $this->errorResponse('Ошибка при получении списка клиник');
            }
        }
        /**
         * Получить детальную информацию о клинике и ее врачах
         */
        public function getClinicDetails(int $id): JsonResponse
        {
            try {
                $clinic = $this->clinicService->getClinicWithDentists($id);
                return $this->response->json([
                    'success' => true,
                    'data' => $clinic,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logError('getClinicDetails', $e);
                return $this->errorResponse('Клиника не найдена', 404);
            }
        }
        /**
         * Создать запись на прием
         */
        public function createAppointment(AppointmentCreateRequest $request): JsonResponse
        {
            try {
                $appointment = $this->appointmentService->bookAppointment(
                    $request->validated() + ['correlation_id' => $this->correlationId]
                );
                return $this->response->json([
                    'success' => true,
                    'data' => $appointment,
                    'message' => 'Запись успешно создана',
                    'correlation_id' => $this->correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logError('createAppointment', $e);
                return $this->errorResponse($e->getMessage());
            }
        }
        /**
         * AI Анализ улыбки через конструктор
         */
        public function analyzeSmile(SmileAnalyzeRequest $request): JsonResponse
        {
            try {
                $photo = $request->file('photo');
                $analysis = $this->smileService->analyzeAndRecommend(
                    $photo,
                    $request->user()->id ?? 0
                );
                return $this->response->json([
                    'success' => true,
                    'data' => $analysis,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logError('analyzeSmile', $e);
                return $this->errorResponse('Ошибка AI анализа');
            }
        }
        /**
         * Получение плана лечения пользователя
         */
        public function getUserTreatmentPlans(Request $request): JsonResponse
        {
            try {
                $plans = $this->planService->getPlansForPatient($request->user()->id);
                return $this->response->json([
                    'success' => true,
                    'data' => $plans,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logError('getUserTreatmentPlans', $e);
                return $this->errorResponse('Планировщик недоступен');
            }
        }
        private function errorResponse(string $message, int $code = 400): JsonResponse
        {
            return $this->response->json([
                'success' => false,
                'message' => $message,
                'correlation_id' => $this->correlationId,
            ], $code);
        }
        private function logError(string $method, \Throwable $e): void
        {
            $this->logger->channel('audit')->error("DentalApi::{$method} failed", [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
}
