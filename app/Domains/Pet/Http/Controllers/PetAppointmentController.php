<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class PetAppointmentController extends Controller
{

    public function __construct(
            private readonly AppointmentService $appointmentService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $appointments = PetAppointment::where('owner_id', $request->user()?->id)
                    ->orWhere('clinic_id', $request->user()->clinics->pluck('id'))
                    ->with(['clinic', 'vet', 'owner', 'service'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointments,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to get appointments', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Appointment not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $appointment = $this->appointmentService->createAppointment(
                    $request->validated(),
                    $correlationId
                );

                $this->logger->info('Pet appointment created', [
                    'correlation_id' => $correlationId,
                    'appointment_id' => $appointment->id ?? null,
                    'tenant_id'      => $appointment->tenant_id ?? null,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create appointment', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create appointment',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function update(Request $request, $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $appointment = PetAppointment::findOrFail($id);
                $this->authorize('update', $appointment);

                $before = $appointment->getAttributes();

                $appointment->update([
                    ...$request->validated(),
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Pet appointment updated', [
                    'correlation_id' => $correlationId,
                    'appointment_id' => $appointment->id,
                    'tenant_id'      => $appointment->tenant_id,
                    'user_id'        => $request->user()?->id,
                    'before'         => $before,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update appointment',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function destroy($id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $appointment = PetAppointment::findOrFail($id);
                $this->authorize('cancel', $appointment);

                $appointment->delete();

                $this->logger->info('Pet appointment deleted', [
                    'correlation_id' => $correlationId,
                    'appointment_id' => $appointment->id,
                    'tenant_id'      => $appointment->tenant_id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Appointment deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to cancel appointment',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function getMedicalRecords(): JsonResponse
        {
            try {
                $records = PetMedicalRecord::where('owner_id', $request->user()?->id)
                    ->with(['clinic', 'vet'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $records,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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

                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'medical_record_create', amount: 0, correlationId: $correlationId ?? '');

                $record = PetMedicalRecord::create([
                    ...$request->validated(),
                    'tenant_id' => tenant()->id,
                    'owner_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                    'recorded_at' => now(),
                ]);

                $this->logger->info('Pet medical record created', [
                    'correlation_id' => $correlationId,
                    'record_id'      => $record->id,
                    'tenant_id'      => $record->tenant_id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $record,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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
                    'total_appointments' => PetAppointment::where('owner_id', $request->user()?->id)->count(),
                    'completed' => PetAppointment::where('owner_id', $request->user()?->id)->where('status', 'completed')->count(),
                    'pending' => PetAppointment::where('owner_id', $request->user()?->id)->where('status', 'pending')->count(),
                    'cancelled' => PetAppointment::where('owner_id', $request->user()?->id)->where('status', 'cancelled')->count(),
                ];

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $stats,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $analytics,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => Str::uuid(),
                ], 403);
            }
        }
}
