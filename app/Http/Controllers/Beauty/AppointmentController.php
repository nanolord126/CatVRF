<?php declare(strict_types=1);

namespace App\Http\Controllers\Beauty;

use App\Http\Controllers\Controller;
use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Services\AppointmentBookingService;
use App\Domains\Beauty\Services\AppointmentCancellationService;
use App\Domains\Beauty\Services\AppointmentRescheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Контроллер управления записями в вертикали Beauty 2026.
 */
final class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentBookingService $bookingService,
        private readonly AppointmentCancellationService $cancellationService,
        private readonly AppointmentRescheduleService $rescheduleService
    ) {}

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
                return response()->json([
                    "success" => false,
                    "message" => $result["reason"] ?? "Отмена невозможна по правилам политики.",
                    "correlation_id" => $correlationId
                ], 403);
            }

            // 2. Выполнение отмены
            $this->bookingService->cancel($appointment, (string)$request->input("reason", "Cancelled by user"));

            return response()->json([
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
            Log::channel("audit")->error("Failed to cancel appointment", [
                "uuid" => $uuid,
                "error" => $e->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return response()->json([
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

            return response()->json([
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
            return response()->json([
                "success" => false,
                "message" => $e->getMessage(),
                "correlation_id" => $correlationId
            ], 400);
        } catch (\Throwable $e) {
            Log::channel("audit")->error("Failed to reschedule appointment", [
                "uuid" => $uuid,
                "error" => $e->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return response()->json([
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

            return response()->json([
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
            return response()->json(["success" => false, "message" => $e->getMessage()], 404);
        }
    }
}
