<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewingAppointmentController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(): JsonResponse
        {
            try {
                $appointments = ViewingAppointment::query()
                    ->where('client_id', auth()->id())
                    ->with('property', 'agent')
                    ->paginate(15);

                return response()->json([
                    'success' => true,
                    'data' => $appointments,
                ]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false], 500);
            }
        }

        public function create(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $request->validate([
                    'property_id' => 'required|exists:properties,id',
                    'datetime' => 'required|date|after:now',
                ]);

                $appointment = ViewingAppointment::create([
                    'tenant_id' => tenant('id'),
                    'property_id' => $request->get('property_id'),
                    'client_id' => auth()->id(),
                    'datetime' => $request->get('datetime'),
                    'status' => 'scheduled',
                ]);

                Log::channel('audit')->info('Viewing appointment created', [
                    'appointment_id' => $appointment->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $appointment,
                ], 201);
            } catch (\Throwable $e) {
                return response()->json(['success' => false], 500);
            }
        }

        public function update(ViewingAppointment $appointment, Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            $this->authorize('update', $appointment);

            try {
                $appointment->update($request->only(['status', 'notes']));

                return response()->json([
                    'success' => true,
                    'data' => $appointment,
                ]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false], 500);
            }
        }

        public function cancel(ViewingAppointment $appointment): JsonResponse
        {
            $this->authorize('cancel', $appointment);

            try {
                $appointment->update(['status' => 'cancelled']);

                return response()->json([
                    'success' => true,
                    'message' => 'Просмотр отменён',
                ]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false], 500);
            }
        }
}
