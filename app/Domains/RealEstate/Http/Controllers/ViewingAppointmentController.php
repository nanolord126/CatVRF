<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ViewingAppointmentController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $appointments = ViewingAppointment::query()
                    ->where('client_id', $request->user()?->id)
                    ->with('property', 'agent')
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointments,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }

        public function create(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $request->validate([
                    'property_id' => 'required|exists:properties,id',
                    'datetime' => 'required|date|after:now',
                ]);

                $appointment = ViewingAppointment::create([
                    'tenant_id' => tenant()?->id,
                    'property_id' => $request->get('property_id'),
                    'client_id' => $request->user()?->id,
                    'datetime' => $request->get('datetime'),
                    'status' => 'scheduled',
                ]);

                $this->logger->info('Viewing appointment created', [
                    'appointment_id' => $appointment->id,
                'correlation_id' => $request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }

        public function update(ViewingAppointment $appointment, Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            $this->authorize('update', $appointment);

            try {
                $appointment->update($request->only(['status', 'notes']));

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $appointment,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }

        public function cancel(ViewingAppointment $appointment): JsonResponse
        {
            $this->authorize('cancel', $appointment);

            try {
                $appointment->update(['status' => 'cancelled']);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Просмотр отменён',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }
}
