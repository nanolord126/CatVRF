<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class MedicalAppointmentController extends Controller
{

    public function __construct(private readonly AppointmentService $appointmentService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function services(): JsonResponse
        {
            try {
                $services = MedicalService::where('is_active', true)->paginate(50);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $services,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch services'], 500);
            }
        }

        public function myAppointments(): JsonResponse
        {
            try {
                $appointments = MedicalAppointment::where('patient_id', $request->user()->id)
                    ->orderBy('scheduled_at', 'desc')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointments,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch appointments'], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {

                $validated = $request->all();
                $appointment = $this->db->transaction(function () use ($validated, $correlationId) {
                    return $this->appointmentService->createAppointment(
                        tenantId: $request->user()->tenant_id,
                        clinicId: ($validated['clinic_id'] ?? null),
                        doctorId: ($validated['doctor_id'] ?? null),
                        patientId: $request->user()->id,
                        serviceId: ($validated['service_id'] ?? null),
                        scheduledAt: ($validated['scheduled_at'] ?? null),
                        notes: ($validated['notes'] ?? null),
                        correlationId: $correlationId,
                    );
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Throwable $e) {
                $this->logger->error('Failed to create appointment', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to create appointment'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $appointment = MedicalAppointment::with(['doctor', 'clinic', 'service'])
                    ->findOrFail($id);

                $this->authorize('view', $appointment);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Appointment not found'], 404);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $appointment = MedicalAppointment::findOrFail($id);
                $this->authorize('update', $appointment);

                $appointment->update([
                    'scheduled_at' => $request->input('scheduled_at', $appointment->scheduled_at),
                    'notes' => $request->input('notes', $appointment->notes),
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);

                $this->logger->info('Appointment updated', ['appointment_id' => $appointment->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $appointment]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Update failed'], 500);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            try {
                $appointment = MedicalAppointment::findOrFail($id);
                $this->authorize('delete', $appointment);

                $appointment = $this->appointmentService->cancelAppointment(
                    appointment: $appointment,
                    reason: 'Cancelled by patient',
                    correlationId: $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $appointment]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Cancel failed'], 500);
            }
        }

        public function complete(Request $request, int $id): JsonResponse
        {
            try {
                $appointment = MedicalAppointment::findOrFail($id);

                $appointment = $this->appointmentService->completeAppointment(
                    appointment: $appointment,
                    diagnosis: $request->input('diagnosis', []),
                    correlationId: $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $appointment]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Complete failed'], 500);
            }
        }

        public function history(int $id): JsonResponse
        {
            try {
                $appointment = MedicalAppointment::findOrFail($id);
                $this->authorize('view', $appointment);

                $records = MedicalRecord::where('patient_id', $appointment->patient_id)
                    ->where('clinic_id', $appointment->clinic_id)
                    ->orderBy('recorded_at', 'desc')
                    ->get();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $records,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
        }

        public function myPrescriptions(): JsonResponse
        {
            try {
                $prescriptions = MedicalPrescription::where('patient_id', $request->user()->id)
                    ->orderBy('issued_at', 'desc')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $prescriptions,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch prescriptions'], 500);
            }
        }

        public function getPrescription(int $id): JsonResponse
        {
            try {
                $prescription = MedicalPrescription::findOrFail($id);

                $this->authorize('view', MedicalAppointment::find($prescription->appointment_id));

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $prescription,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Prescription not found'], 404);
            }
        }

        public function myRecords(): JsonResponse
        {
            try {
                $records = MedicalRecord::where('patient_id', $request->user()->id)
                    ->orderBy('recorded_at', 'desc')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $records,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch records'], 500);
            }
        }

        public function getRecord(int $id): JsonResponse
        {
            try {
                $record = MedicalRecord::findOrFail($id);

                if ($record->patient_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
                    abort(403);
                }

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $record,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Record not found'], 404);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $appointments = MedicalAppointment::paginate(50);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointments,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch appointments'], 500);
            }
        }

        public function updateStatus(Request $request, int $id): JsonResponse
        {
            try {
                $appointment = MedicalAppointment::findOrFail($id);

                $appointment->update([
                    'status' => $request->input('status'),
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);

                $this->logger->info('Appointment status updated', [
                    'appointment_id' => $appointment->id,
                    'status' => $request->input('status'),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $appointment]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Update failed'], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $month = now()->month;
                $year = now()->year;

                $appointments = MedicalAppointment::whereMonth('scheduled_at', $month)
                    ->whereYear('scheduled_at', $year)
                    ->get();

                $analytics = [
                    'total_appointments' => $appointments->count(),
                    'total_revenue' => $appointments->sum('price'),
                    'total_commission' => $appointments->sum('commission_amount'),
                    'average_price' => $appointments->avg('price'),
                    'by_status' => $appointments->groupBy('status')->map->count(),
                ];

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $analytics,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Analytics failed'], 500);
            }
        }
}
