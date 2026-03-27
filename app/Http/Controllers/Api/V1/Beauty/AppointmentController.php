declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Beauty;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Beauty\CreateAppointmentRequest;
use App\Http\Requests\Beauty\ConfirmAppointmentRequest;
use App\Models\Beauty\Appointment;
use App\Models\Beauty\Service;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * Beauty Appointment API Controller.
 * Workflow: Create → Hold Wallet → Fraud Check → Confirm → Payment Init.
 *
 * Commission: 14% to salon.
 * Hold mechanics: on create, release on cancel.
 */
final class AppointmentController extends BaseApiController
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly WalletService $walletService,
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
            return DB::transaction(function () use ($request, $correlationId, $tenantId) {
                // 1. Fraud check перед созданием
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => 'beauty_appointment',
                    'amount' => $request->integer('price'),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);
                if ($fraudResult['decision'] === 'block') {
                    Log::channel('fraud_alert')->warning('Beauty appointment blocked', [
                        'correlation_id' => $correlationId,
                        'user_id' => auth()->id(),
                        'ml_score' => $fraudResult['score'],
                    ]);
                    return response()->json([
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
                    'user_id' => auth()->id(),
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
                Log::channel('audit')->info('Beauty appointment created', [
                    'correlation_id' => $correlationId,
                    'appointment_id' => $appointment->id,
                    'user_id' => auth()->id(),
                    'salon_id' => $request->integer('beauty_salon_id'),
                    'price' => $request->integer('price'),
                ]);
                return response()->json([
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
            Log::channel('audit')->error('Beauty appointment creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
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
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 403);
        }
        return response()->json([
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
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 403);
        }
        try {
            return DB::transaction(function () use ($appointment, $request, $correlationId) {
                // Проверить статус платежа
                if ($request->input('payment_status') !== 'captured') {
                    return response()->json([
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
                Log::channel('audit')->info('Beauty appointment confirmed', [
                    'correlation_id' => $correlationId,
                    'appointment_id' => $appointment->id,
                    'user_id' => auth()->id(),
                ]);
                return response()->json([
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
            Log::channel('audit')->error('Appointment confirmation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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
            return DB::transaction(function () use ($appointment, $correlationId) {
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
                Log::channel('audit')->info('Beauty appointment cancelled', [
                    'correlation_id' => $correlationId,
                    'appointment_id' => $appointment->id,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment cancelled',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Appointment cancellation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
