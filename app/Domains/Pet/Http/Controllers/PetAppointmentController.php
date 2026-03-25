<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;

use App\Domains\Pet\Models\PetAppointment;
use App\Domains\Pet\Models\PetMedicalRecord;
use App\Domains\Pet\Services\AppointmentService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PetAppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $appointments = PetAppointment::where('owner_id', auth()->id())
                ->orWhere('clinic_id', auth()->user()->clinics->pluck('id'))
                ->with(['clinic', 'vet', 'owner', 'service'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            $this->log->error('Failed to get appointments', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve appointments',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $appointment = PetAppointment::with(['clinic', 'vet', 'owner', 'service'])
                ->findOrFail($id);

            $this->authorize('view', $appointment);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $appointment = $this->appointmentService->createAppointment(
                $request->validated(),
                $correlationId
            );

            $this->log->channel('audit')->info('Pet appointment created', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id ?? null,
                'tenant_id'      => $appointment->tenant_id ?? null,
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $this->log->error('Failed to create appointment', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create appointment',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $appointment = PetAppointment::findOrFail($id);
            $this->authorize('update', $appointment);

            $before = $appointment->getAttributes();

            $appointment->update([
                ...$request->validated(),
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('Pet appointment updated', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
                'tenant_id'      => $appointment->tenant_id,
                'user_id'        => auth()->id(),
                'before'         => $before,
            ]);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update appointment',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $appointment = PetAppointment::findOrFail($id);
            $this->authorize('cancel', $appointment);

            $appointment->delete();

            $this->log->channel('audit')->info('Pet appointment deleted', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
                'tenant_id'      => $appointment->tenant_id,
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete appointment',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function cancel($id): JsonResponse
    {
        try {
            $appointment = PetAppointment::findOrFail($id);
            $this->authorize('cancel', $appointment);
            $correlationId = Str::uuid()->toString();

            $appointment = $this->appointmentService->cancelAppointment($appointment, $correlationId);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel appointment',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getMedicalRecords(): JsonResponse
    {
        try {
            $records = PetMedicalRecord::where('owner_id', auth()->id())
                ->with(['clinic', 'vet'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $records,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve medical records',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function createMedicalRecord(Request $request): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $this->fraudControlService->check(auth()->id() ?? 0, 'medical_record_create', 0, request()->ip(), null, $correlationId);

            $record = PetMedicalRecord::create([
                ...$request->validated(),
                'tenant_id' => tenant()->id,
                'owner_id' => auth()->id(),
                'correlation_id' => $correlationId,
                'uuid' => Str::uuid(),
                'recorded_at' => now(),
            ]);

            $this->log->channel('audit')->info('Pet medical record created', [
                'correlation_id' => $correlationId,
                'record_id'      => $record->id,
                'tenant_id'      => $record->tenant_id,
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $record,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create medical record',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_appointments' => PetAppointment::where('owner_id', auth()->id())->count(),
                'completed' => PetAppointment::where('owner_id', auth()->id())->where('status', 'completed')->count(),
                'pending' => PetAppointment::where('owner_id', auth()->id())->where('status', 'pending')->count(),
                'cancelled' => PetAppointment::where('owner_id', auth()->id())->where('status', 'cancelled')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function analyticsAdmin(): JsonResponse
    {
        try {
            $this->authorize('create', PetAppointment::class);

            $analytics = [
                'total_appointments' => PetAppointment::count(),
                'completed' => PetAppointment::where('status', 'completed')->count(),
                'cancelled' => PetAppointment::where('status', 'cancelled')->count(),
                'avg_commission' => PetAppointment::where('payment_status', 'paid')->avg('commission_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'correlation_id' => Str::uuid(),
            ], 403);
        }
    }
}
