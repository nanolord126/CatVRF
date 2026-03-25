<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Services\AppointmentService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AppointmentController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $appointments = Appointment::where('user_id', auth()->id())
                ->with('service', 'master', 'salon')
                ->paginate(20);

            $correlationId = Str::uuid()->toString();
            $this->log->channel('audit')->info('Beauty appointments listed', [
                'user_id' => auth()->id(),
                'count' => $appointments->count(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            $this->log->error('Beauty appointment listing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $appointment = Appointment::with('service', 'master', 'salon')->findOrFail($id);

            if ($appointment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => Str::uuid(),
                ], 403);
            }

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

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'create_appointment',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $appointment = $this->db->transaction(function () use ($correlationId) {
                return Appointment::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'user_id' => auth()->id(),
                    'service_id' => request('service_id'),
                    'master_id' => request('master_id'),
                    'salon_id' => request('salon_id'),
                    'appointment_date' => request('appointment_date'),
                    'appointment_time' => request('appointment_time'),
                    'status' => 'pending',
                    'price' => request('price'),
                    'notes' => request('notes'),
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->log->channel('audit')->info('Beauty appointment created', [
                'appointment_id' => $appointment->id,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            $this->log->error('Beauty appointment creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $appointment = Appointment::findOrFail($id);

            if ($appointment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $this->db->transaction(function () use ($appointment, $correlationId) {
                $appointment->update([
                    'status' => 'cancelled',
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->log->channel('audit')->info('Beauty appointment cancelled', [
                'appointment_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment cancelled',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            $this->log->error('Beauty appointment cancellation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
