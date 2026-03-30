<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Medical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalAppointmentController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param AppointmentService $appointmentService Основная бизнес-логика
         */
        public function __construct(
            private readonly AppointmentService $appointmentService
        ) {
        }
        /**
         * Создание новой записи к врачу.
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? Str::uuid()->toString();
            try {
                // 1. Валидация входных данных (В ЛЮТОМ РЕЖИМЕ — строгость по ФЗ-152)
                $validated = $request->validate([
                    'doctor_id' => 'required|exists:doctors,id',
                    'service_id' => 'required|exists:medical_services,id',
                    'starts_at' => 'required|date|after:now',
                    'duration_minutes' => 'required|integer|min:5|max:120',
                    'total_price' => 'required|integer',
                    'prepayment_amount' => 'required|integer',
                    'tenant_id' => 'required|integer',
                    'clinic_id' => 'required|integer',
                    'patient_id' => 'required|integer',
                    'metadata' => 'nullable|array',
                ]);
                // 2. Создание DTO (Layer 4)
                $dto = AppointmentData::fromArray($validated, $correlationId);
                // 3. Вызов сервиса записи (Layer 3)
                $appointment = $this->appointmentService->bookDoctor($dto);
                // 4. Лог аудита (Layer 6)
                Log::channel('audit')->info('Medical Appointment Created via API', [
                    'correlation_id' => $correlationId,
                    'appointment_uuid' => $appointment->uuid,
                    'patient_id' => $dto->patientId,
                    'doctor_id' => $dto->doctorId,
                    'user_agent' => $request->userAgent(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment successfully booked',
                    'data' => [
                        'uuid' => $appointment->uuid,
                        'starts_at' => $appointment->starts_at->toIso8601String(),
                        'status' => $appointment->status,
                        'correlation_id' => $correlationId,
                    ]
                ], 201);
            } catch (\Throwable $e) {
                // 5. Обработка всех ошибок через correlation_id
                Log::channel('audit')->error('Failed to book medical appointment', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Booking failed',
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * Отмена записи (с возвратом средств в Wallet).
         *
         * @param string $uuid
         * @param Request $request
         * @return JsonResponse
         */
        public function cancel(string $uuid, Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
            try {
                $appointment = \App\Domains\Medical\Models\Appointment::where('uuid', $uuid)->firstOrFail();
                // Проверка прав (Policy Layer 6)
                // if (auth()->user()->cannot('cancel', $appointment)) { ... }
                $this->appointmentService->cancelAppointmentByPatient(
                    appointmentId: (int)$appointment->id,
                    reason: $request->get('reason', 'Cancelled via Mobile App'),
                    correlationId: $correlationId
                );
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment cancelled',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cancellation failed',
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }
        /**
         * Получение списка свободных врачей (для витрины).
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function listDoctors(Request $request): JsonResponse
        {
            $tenantId = (int)$request->header('X-Tenant-ID') ?? auth()->user()?->tenant_id;
            $doctors = \App\Domains\Medical\Models\Doctor::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->withCount('medicalAppointments')
                ->orderByDesc('rating')
                ->paginate(10);
            return response()->json([
                'success' => true,
                'data' => $doctors,
                'correlation_id' => $request->get('correlation_id'),
            ]);
        }
}
