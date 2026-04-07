<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class AppointmentController extends Controller
{


    public function __construct(
        private AppointmentService $service,
        private LoggerInterface $logger,
    ) {
    }
        /**
         * Создать запись (POST /appointments).
         */
        public function store(CreateAppointmentRequest $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $this->logger->info('API Request: Create Appointment', [
                    'correlation_id' => $correlationId,
                    'data' => $request->validated()
                ]);

                $appointment = $this->service->createAppointment(
                    data: $request->validated(),
                    correlationId: $correlationId
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                    'correlation_id' => $correlationId
                ], 201);

            } catch (\Throwable $e) {
                $this->logger->error('API Error: Create Appointment Failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Не удалось создать запись: ' . $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 400);
            }
        }

        /**
         * Завершить запись (PATCH /appointments/{appointment}/complete).
         */
        public function complete(Appointment $appointment): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $this->service->completeAppointment($appointment, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Запись успешно завершена и оплачена.',
                    'correlation_id' => $correlationId
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('API Error: Complete Appointment Failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 400);
            }
        }

        /**
         * Отменить запись (POST /appointments/{appointment}/cancel).
         */
        public function cancel(Appointment $appointment): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $this->service->cancelAppointment($appointment, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Запись успешно отменена.',
                    'correlation_id' => $correlationId
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('API Error: Cancel Appointment Failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при отмене: ' . $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 400);
            }
        }
}
