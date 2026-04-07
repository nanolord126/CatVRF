<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Beauty;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class AppointmentController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraudService,
            private readonly WalletService $walletService,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * POST /api/v1/beauty/appointments
         * Создать запись на услугу красоты.
         *
         * @return JsonResponse
         */
        public function store(CreateAppointmentRequest $request): JsonResponse
        {
            $correlationId = $request->getCorrelationId();
            $tenantId = $request->getTenantId();
            try {
                return $this->db->transaction(function () use ($request, $correlationId, $tenantId) {
                    // 1. Fraud check перед созданием
                    $fraudResult = $this->fraudService->scoreOperation([
                        'type' => 'beauty_appointment',
                        'amount' => $request->integer('price'),
                        'user_id' => $this->guard->id(),
                        'ip_address' => $request->ip(),
                        'correlation_id' => $correlationId,
                    ]);
                    if ($fraudResult['decision'] === 'block') {
                        $this->logger->channel('fraud_alert')->warning('Beauty appointment blocked', [
                            'correlation_id' => $correlationId,
                            'user_id' => $this->guard->id(),
                            'ml_score' => $fraudResult['score'],
                        ]);
                        return $this->response->json([
                            'success' => false,
                            'message' => 'Appointment creation blocked due to fraud check',
                            'correlation_id' => $correlationId,
                        ], 403)->send();
                    }
                    // 2. Создать запись
                    $appointment = Appointment::factory()->create([
                        'tenant_id' => $tenantId,
                        'beauty_salon_id' => $request->integer('beauty_salon_id'),
                        'master_id' => $request->integer('master_id'),
                        'service_id' => $request->integer('service_id'),
                        'user_id' => $this->guard->id(),
                        'appointment_datetime' => $request->input('appointment_datetime'),
                        'price' => $request->integer('price'),
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                        'uuid' => Str::uuid(),
                    ]);
                    // 3. Hold сумму в кошельке
                    $this->walletService->reserveStock(
                        item_id: $appointment->id,
                        quantity: $request->integer('price'),
                        source_type: 'beauty_appointment',
                        source_id: $appointment->id,
                        correlation_id: $correlationId,
                    );
                    // 4. Логирование
                    $this->logger->channel('audit')->info('Beauty appointment created', [
                        'correlation_id' => $correlationId,
                        'appointment_id' => $appointment->id,
                        'user_id' => $this->guard->id(),
                        'salon_id' => $request->integer('beauty_salon_id'),
                        'price' => $request->integer('price'),
                    ]);
                    return $this->response->json([
                        'success' => true,
                        'message' => 'Appointment created successfully',
                        'correlation_id' => $correlationId,
                        'data' => [
                            'id' => $appointment->id,
                            'uuid' => $appointment->uuid,
                            'status' => $appointment->status,
                            'price' => $appointment->price,
                            'datetime' => $appointment->appointment_datetime,
                        ],
                    ], 201);
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Beauty appointment creation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Failed to create appointment',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * GET /api/v1/beauty/appointments/{id}
         * Получить запись по ID.
         */
        public function show(Appointment $appointment, CreateAppointmentRequest $request): JsonResponse
        {
            $correlationId = $request->getCorrelationId();
            if ($appointment->tenant_id !== $request->getTenantId()) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }
            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'id' => $appointment->id,
                    'uuid' => $appointment->uuid,
                    'status' => $appointment->status,
                    'price' => $appointment->price,
                    'datetime' => $appointment->appointment_datetime,
                    'master_name' => $appointment->master->full_name ?? null,
                    'service_name' => $appointment->service->name ?? null,
                ],
            ], 200);
        }
        /**
         * POST /api/v1/beauty/appointments/{id}/confirm
         * Подтвердить запись после оплаты.
         */
        public function confirm(
            Appointment $appointment,
            ConfirmAppointmentRequest $request,
        ): JsonResponse {
            $correlationId = $request->getCorrelationId();
            $tenantId = $request->getTenantId();
            if ($appointment->tenant_id !== $tenantId) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }
            try {
                return $this->db->transaction(function () use ($appointment, $request, $correlationId) {
                    // Проверить статус платежа
                    if ($request->input('payment_status') !== 'captured') {
                        return $this->response->json([
                            'success' => false,
                            'message' => 'Payment not captured',
                            'correlation_id' => $correlationId,
                        ], 400)->send();
                    }
                    // Обновить статус записи
                    $appointment->update([
                        'status' => 'confirmed',
                        'correlation_id' => $correlationId,
                    ]);
                    $this->logger->channel('audit')->info('Beauty appointment confirmed', [
                        'correlation_id' => $correlationId,
                        'appointment_id' => $appointment->id,
                        'user_id' => $this->guard->id(),
                    ]);
                    return $this->response->json([
                        'success' => true,
                        'message' => 'Appointment confirmed',
                        'correlation_id' => $correlationId,
                        'data' => [
                            'id' => $appointment->id,
                            'status' => $appointment->status,
                        ],
                    ], 200);
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Appointment confirmation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Confirmation failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * POST /api/v1/beauty/appointments/{id}/cancel
         * Отменить запись и вернуть деньги.
         */
        public function cancel(
            Appointment $appointment,
            CreateAppointmentRequest $request,
        ): JsonResponse {
            $correlationId = $request->getCorrelationId();
            try {
                return $this->db->transaction(function () use ($appointment, $correlationId) {
                    $appointment->update([
                        'status' => 'cancelled',
                        'correlation_id' => $correlationId,
                    ]);
                    // Release hold сумм
                    $this->walletService->releaseStock(
                        item_id: $appointment->id,
                        quantity: $appointment->price,
                        source_type: 'beauty_appointment',
                        source_id: $appointment->id,
                    );
                    $this->logger->channel('audit')->info('Beauty appointment cancelled', [
                        'correlation_id' => $correlationId,
                        'appointment_id' => $appointment->id,
                    ]);
                    return $this->response->json([
                        'success' => true,
                        'message' => 'Appointment cancelled',
                        'correlation_id' => $correlationId,
                    ], 200);
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Appointment cancellation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Cancellation failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
