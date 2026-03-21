<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Models\CarWashBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления бронями мойки.
 * Production 2026.
 */
final class CarWashBookingController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $bookings = CarWashBooking::query()
                ->where('tenant_id', tenant('id') ?? 1)
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $bookings,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении броней',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid()->toString();

            $request->validate([
                'client_id' => 'required|exists:users,id',
                'wash_type' => 'required|string',
                'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            ]);

            $booking = DB::transaction(function () use ($request, $correlationId) {
                $booking = CarWashBooking::create([
                    'tenant_id' => tenant('id') ?? 1,
                    'client_id' => $request->get('client_id'),
                    'wash_type' => $request->get('wash_type'),
                    'scheduled_at' => $request->get('scheduled_at'),
                    'status' => 'pending',
                    'price' => 50000, 
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Car wash booking created', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);

                return $booking;
            });

            return response()->json([
                'success' => true,
                'data' => $booking,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании брони',
            ], 500);
        }
    }

    public function show(CarWashBooking $booking): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    public function cancel(CarWashBooking $booking): JsonResponse
    {
        try {
            $this->authorize('cancel', $booking);

            $booking->update(['status' => 'cancelled']);

            Log::channel('audit')->info('Car wash booking cancelled', [
                'booking_id' => $booking->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Бронь отменена',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене брони',
            ], 500);
        }
    }

    public function availability(Request $request): JsonResponse
    {
        $washTypes = ['standard' => 'Стандартная', 'premium' => 'Премиум', 'express' => 'Экспресс'];

        return response()->json([
            'success' => true,
            'types' => $washTypes,
        ]);
    }

    public function washTypes(Request $request): JsonResponse
    {
        $types = [
            'standard' => ['name' => 'Стандартная мойка', 'price' => 50000, 'duration' => 30],
            'premium' => ['name' => 'Премиум мойка', 'price' => 80000, 'duration' => 45],
            'express' => ['name' => 'Экспресс мойка', 'price' => 35000, 'duration' => 20],
        ];

        return response()->json([
            'success' => true,
            'types' => $types,
        ]);
    }
}
