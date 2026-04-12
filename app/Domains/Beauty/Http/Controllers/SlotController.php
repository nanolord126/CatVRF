<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class SlotController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request, int $master): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $slots = $this->db->table('beauty_slots')
            ->where('master_id', $master)
            ->where('date', '>=', now()->toDateString())
            ->where('is_available', true)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $this->logger->info('Slots listed', [
            'correlation_id' => $correlationId,
            'master_id' => $master,
            'count' => $slots->count(),
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $slots,
        ]);
    }

    public function reserve(Request $request, int $slot): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $slotRow = $this->db->table('beauty_slots')
            ->where('id', $slot)
            ->where('is_available', true)
            ->first();

        if ($slotRow === null) {
            return new JsonResponse([
                'correlation_id' => $correlationId,
                'message' => 'Слот недоступен или не найден',
            ], 409);
        }

        $this->db->transaction(function () use ($slot, $request, $correlationId) {
            $this->db->table('beauty_slots')
                ->where('id', $slot)
                ->update([
                    'is_available' => false,
                    'reserved_by' => $request->user()?->id,
                    'reserved_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->db->table('beauty_reservations')->insert([
                'slot_id' => $slot,
                'user_id' => $request->user()?->id,
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'expires_at' => now()->addMinutes(20),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->logger->info('Slot reserved', [
            'correlation_id' => $correlationId,
            'slot_id' => $slot,
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message' => 'Слот зарезервирован на 20 минут',
        ]);
    }

    public function release(Request $request, int $slot): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($slot) {
            $this->db->table('beauty_slots')
                ->where('id', $slot)
                ->update([
                    'is_available' => true,
                    'reserved_by' => null,
                    'reserved_at' => null,
                    'updated_at' => now(),
                ]);

            $this->db->table('beauty_reservations')
                ->where('slot_id', $slot)
                ->delete();
        });

        $this->logger->info('Slot released', [
            'correlation_id' => $correlationId,
            'slot_id' => $slot,
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message' => 'Слот освобождён',
        ]);
    }

    public function generate(Request $request, int $master): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_hour' => 'required|integer|min:0|max:23',
            'end_hour' => 'required|integer|min:1|max:24',
            'slot_duration_minutes' => 'required|integer|in:30,60,90,120',
        ]);

        $slots = [];
        $startHour = $validated['start_hour'];
        $endHour = $validated['end_hour'];
        $duration = $validated['slot_duration_minutes'];

        $this->db->transaction(function () use ($master, $validated, $startHour, $endHour, $duration, $correlationId, &$slots) {
            $currentMinutes = $startHour * 60;
            $endMinutes = $endHour * 60;

            while ($currentMinutes + $duration <= $endMinutes) {
                $startTime = sprintf('%02d:%02d', intdiv($currentMinutes, 60), $currentMinutes % 60);
                $endTime = sprintf('%02d:%02d', intdiv($currentMinutes + $duration, 60), ($currentMinutes + $duration) % 60);

                $id = $this->db->table('beauty_slots')->insertGetId([
                    'master_id' => $master,
                    'date' => $validated['date'],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_available' => true,
                    'uuid' => (string) Str::uuid(),
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $slots[] = ['id' => $id, 'start_time' => $startTime, 'end_time' => $endTime];
                $currentMinutes += $duration;
            }
        });

        $this->logger->info('Slots generated', [
            'correlation_id' => $correlationId,
            'master_id' => $master,
            'count' => count($slots),
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message' => 'Слоты сгенерированы',
            'slots' => $slots,
        ], 201);
    }
}
