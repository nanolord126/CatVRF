<?php declare(strict_types=1);

namespace App\Modules\Beauty\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Beauty\Models\BeautySalon;
use App\Modules\Beauty\Models\Appointment;
use App\Modules\Beauty\Http\Requests\StoreBeautySalonRequest;
use App\Modules\Beauty\Http\Requests\StoreAppointmentRequest;
use App\Modules\Beauty\Services\BeautyService;
use App\Modules\Beauty\Services\BookingService;
use App\Domains\Finances\Services\Security\FraudControlService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Контроллер Красоты (салоны, мастера, записи).
 * Production 2026.
 */
final class BeautyController extends Controller
{
    public function __construct(
        private readonly BeautyService $beautyService,
        private readonly BookingService $bookingService,
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Получить все салоны красоты tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $this->log->channel('audit')->info('beauty.salons.index.start', [
                'correlation_id' => $correlationId,
                'tenant_id' => tenant('id'),
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $salons = BeautySalon::where('tenant_id', tenant('id'))
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $this->log->channel('audit')->info('beauty.salons.index.success', [
                'correlation_id' => $correlationId,
                'count' => $salons->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $salons,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.salons.index.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении салонов',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Создать салон красоты.
     */
    public function store(StoreBeautySalonRequest $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $this->log->channel('audit')->info('beauty.salon.create.start', [
                'correlation_id' => $correlationId,
                'name' => $request->name,
            ]);

            $salon = $this->db->transaction(function () use ($request, $correlationId) {
                return $this->beautyService->createSalon(
                    tenantId: tenant('id'),
                    name: $request->name,
                    address: $request->address,
                    phone: $request->phone,
                    email: $request->email,
                    description: $request->description,
                    workingHours: $request->working_hours ?? [],
                    correlationId: $correlationId,
                );
            });

            $this->log->channel('audit')->info('beauty.salon.create.success', [
                'correlation_id' => $correlationId,
                'salon_id' => $salon->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $salon,
                'correlation_id' => (string) $correlationId,
            ], 201);
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.salon.create.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании салона',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Получить салон.
     */
    public function show(BeautySalon $salon): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $this->authorize('view', $salon);
            
            $this->log->channel('audit')->info('beauty.salon.show', [
                'correlation_id' => $correlationId,
                'salon_id' => $salon->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $salon->load(['masters', 'services']),
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.salon.show.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Салон не найден',
                'correlation_id' => (string) $correlationId,
            ], 404);
        }
    }

    /**
     * Создать запись на услугу.
     */
    public function createAppointment(StoreAppointmentRequest $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            // Fraud check
            $fraudScore = $this->fraudControl->assessRisk(auth()->user(), [
                'amount' => $request->price ?? 0,
                'type' => 'beauty_appointment',
                'correlation_id' => $correlationId,
            ]);

            if ($fraudScore > 80) {
                $this->log->channel('audit')->warning('beauty.appointment.fraud.blocked', [
                    'correlation_id' => $correlationId,
                    'fraud_score' => $fraudScore,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Операция заблокирована системой безопасности',
                    'correlation_id' => (string) $correlationId,
                ], 403);
            }

            $this->log->channel('audit')->info('beauty.appointment.create.start', [
                'correlation_id' => $correlationId,
                'salon_id' => $request->salon_id,
                'service_id' => $request->service_id,
            ]);

            $appointment = $this->db->transaction(function () use ($request, $correlationId) {
                return $this->bookingService->createAppointment(
                    salonId: $request->salon_id,
                    serviceId: $request->service_id,
                    masterId: $request->master_id,
                    clientId: auth()->id(),
                    tenantId: tenant('id'),
                    dateTime: $request->datetime,
                    notes: $request->notes,
                    correlationId: $correlationId,
                );
            });

            $this->log->channel('audit')->info('beauty.appointment.create.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'correlation_id' => (string) $correlationId,
            ], 201);
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.appointment.create.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании записи',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Отменить запись.
     */
    public function cancelAppointment(Appointment $appointment): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $this->authorize('delete', $appointment);

            $this->log->channel('audit')->info('beauty.appointment.cancel.start', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
            ]);

            $cancelled = $this->db->transaction(function () use ($appointment, $correlationId) {
                return $this->bookingService->cancelAppointment(
                    appointment: $appointment,
                    correlationId: $correlationId,
                );
            });

            $this->log->channel('audit')->info('beauty.appointment.cancel.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $cancelled->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $cancelled,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.appointment.cancel.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене записи',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Получить доступные слоты записи.
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $slots = $this->bookingService->getAvailableSlots(
                salonId: $request->salon_id,
                masterId: $request->master_id,
                serviceId: $request->service_id,
                date: $request->date,
            );

            $this->log->channel('audit')->info('beauty.slots.check', [
                'correlation_id' => $correlationId,
                'salon_id' => $request->salon_id,
                'slots_count' => count($slots),
            ]);

            return response()->json([
                'success' => true,
                'data' => $slots,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.slots.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении слотов',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Завершить услугу и списать расходники.
     */
    public function completeAppointment(Appointment $appointment): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $this->authorize('update', $appointment);

            $this->log->channel('audit')->info('beauty.appointment.complete.start', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
            ]);

            $completed = $this->db->transaction(function () use ($appointment, $correlationId) {
                return $this->bookingService->completeAppointment(
                    appointment: $appointment,
                    correlationId: $correlationId,
                );
            });

            $this->log->channel('audit')->info('beauty.appointment.complete.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $completed->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $completed,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.appointment.complete.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при завершении услуги',
                'correlation_id' => (string) $correlationId,
            ], 500);
}
