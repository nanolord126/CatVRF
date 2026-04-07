<?php declare(strict_types=1);

namespace App\Http\Controllers\Beauty;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class AppointmentController extends Controller
{

    public function __construct(
            private readonly AppointmentBookingService $bookingService,
            private readonly AppointmentCancellationService $cancellationService,
            private readonly AppointmentRescheduleService $rescheduleService,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    ) {
            // PRODUCTION-READY 2026 CANON: Middleware для Beauty вертикали
             // Авторизация обязательна
             // 50 запросов/мин для Beauty
             // Определение режима B2C/B2B
             // Tenant scoping
            // Fraud check только для мутаций (бронирование, отмена, перенос)
            $this->middleware(
                'fraud-check',
                ['only' => ['store', 'cancel', 'reschedule']]
            );
        }
        /**
         * Отмена бронирования клиентом с расчетом штрафов.
         */
        public function cancel(Request $request, string $uuid): JsonResponse
        {
            $correlationId = $request->header("X-Correlation-ID") ?? (string)Str::uuid();
            try {
                $appointment = Appointment::where("uuid", $uuid)
                    ->where("tenant_id", tenant("id"))
                    ->firstOrFail();
                // 1. Расчет условий отмены
                $result = $this->cancellationService->calculateRefund($appointment, Carbon::now());
                if (!$result["is_cancellable"]) {
                    return $this->response->json([
                        "success" => false,
                        "message" => $result["reason"] ?? "Отмена невозможна по правилам политики.",
                        "correlation_id" => $correlationId
                    ], 403);
                }
                // 2. Выполнение отмены
                $this->bookingService->cancel($appointment, (string)$request->input("reason", "Cancelled by user"));
                return $this->response->json([
                    "success" => true,
                    "message" => "Бронирование успешно отменено.",
                    "cancellation_details" => [
                        "policy" => $appointment->cancellation_policy,
                        "penalty_percent" => $result["penalty_percent"],
                        "penalty_amount"  => $result["penalty_amount"] / 100, // В рублях для UI
                        "refund_amount"   => $result["refund_amount"] / 100,
                    ],
                    "correlation_id" => $correlationId
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel("audit")->error("Failed to cancel appointment", [
                    "uuid" => $uuid,
                    "error" => $e->getMessage(),
                    "correlation_id" => $correlationId
                ]);
                return $this->response->json([
                    "success" => false,
                    "message" => "Не удалось отменить бронирование: " . $e->getMessage(),
                    "correlation_id" => $correlationId
                ], 400);
            }
        }
        /**
         * Перенос записи (Rescheduling).
         */
        public function reschedule(Request $request, string $uuid): JsonResponse
        {
            $correlationId = $request->header("X-Correlation-ID") ?? (string)Str::uuid();
            $request->validate([
                "new_start_time" => "required|date|after:now",
            ]);
            try {
                $appointment = Appointment::where("uuid", $uuid)
                    ->where("tenant_id", tenant("id"))
                    ->firstOrFail();
                $newStartTime = Carbon::parse($request->get("new_start_time"));
                // Выполняем перенос через сервис
                $rescheduled = $this->bookingService->reschedule($appointment, $newStartTime);
                return $this->response->json([
                    "success" => true,
                    "message" => "Запись успешно перенесена.",
                    "data" => [
                        "new_start" => $rescheduled->datetime_start->toIso8601String(),
                        "fee_percent" => $rescheduled->metadata["reschedule_fee_percent"] ?? 0,
                        "fee_amount" => ($rescheduled->metadata["reschedule_fee_amount"] ?? 0) / 100,
                    ],
                    "correlation_id" => $correlationId,
                ]);
            } catch (\InvalidArgumentException $e) {
                return $this->response->json([
                    "success" => false,
                    "message" => $e->getMessage(),
                    "correlation_id" => $correlationId
                ], 400);
            } catch (\Throwable $e) {
                $this->logger->channel("audit")->error("Failed to reschedule appointment", [
                    "uuid" => $uuid,
                    "error" => $e->getMessage(),
                    "correlation_id" => $correlationId
                ]);
                return $this->response->json([
                    "success" => false,
                    "message" => "Ошибка при переносе записи: " . $e->getMessage(),
                    "correlation_id" => $correlationId
                ], 500);
            }
        }
        /**
         * Показать таблицу штрафов и условий для текущей записи.
         */
        public function getPolicySummary(Request $request, string $uuid): JsonResponse
        {
            try {
                $appointment = Appointment::where("uuid", $uuid)->firstOrFail();
                // Пример расчета если отменить прямо сейчас
                $cancelPreview = $this->cancellationService->calculateRefund($appointment, Carbon::now());
                // Пример расчета если перенести
                $reschedulePreview = $this->rescheduleService->calculateRescheduleFee(
                    $appointment,
                    (clone $appointment->datetime_start)->addDay(),
                    Carbon::now()
                );
                return $this->response->json([
                    "success" => true,
                    "policy" => $appointment->cancellation_policy,
                    "previews" => [
                        "cancel_now" => [
                            "penalty_percent" => $cancelPreview["penalty_percent"],
                            "refund_amount" => $cancelPreview["refund_amount"] / 100,
                        ],
                        "reschedule_example" => [
                            "fee_percent" => $reschedulePreview["fee_percent"],
                            "fee_amount" => $reschedulePreview["fee_amount"] / 100,
                        ]
                    ]
                ]);
            } catch (\Throwable $e) {
                return $this->response->json(["success" => false, "message" => $e->getMessage()], 404);
            }
        }
}
