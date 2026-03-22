<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use Illuminate\Support\Facades\DB;

final class PhotoSessionService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,)
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);

    }

    /**
     * Забронировать фотосессию
     */
    public function bookPhotoSession(
        int $photographerId,
        string $eventType,
        string $sessionDate,
        int $durationMinutes,
        string $correlationId,
    ): int {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);

        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $sessionId = DB::transaction(function () use ($photographerId, $eventType, $sessionDate, $durationMinutes, $correlationId) {
                $sessionId = DB::table('photo_sessions')->insertGetId([
                    'photographer_id' => $photographerId,
                    'event_type' => $eventType,
                    'session_date' => $sessionDate,
                    'duration_minutes' => $durationMinutes,
                    'status' => 'booked',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('Photo session booked', [
                    'session_id' => $sessionId,
                    'photographer_id' => $photographerId,
                    'event_type' => $eventType,
                    'correlation_id' => $correlationId,
                ]);

                return $sessionId;
            });

            return $sessionId;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Photo session booking failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Завершить фотосессию
     */
    public function completePhotoSession(int $sessionId, int $photosCount, string $correlationId): bool
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);

        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            DB::transaction(function () use ($sessionId, $photosCount, $correlationId) {
                DB::table('photo_sessions')
                    ->where('id', $sessionId)
                    ->update(['status' => 'completed', 'photos_count' => $photosCount, 'completed_at' => now()]);

                Log::channel('audit')->info('Photo session completed', [
                    'session_id' => $sessionId,
                    'photos_count' => $photosCount,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Photo session completion failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
