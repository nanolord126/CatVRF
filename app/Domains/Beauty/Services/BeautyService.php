<?php

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\BeautySalon;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BeautyService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function createSalon(array $data): BeautySalon
    {
        try {
            return DB::transaction(function () use ($data) {
                $salon = BeautySalon::create([...$data, 'tenant_id' => tenant()->id]);
                AuditLog::create([
                    'entity_type' => 'BeautySalon',
                    'entity_id' => $salon->id,
                    'action' => 'create',
                    'correlation_id' => $this->correlationId,
                    'user_id' => auth()->id(),
                ]);
                return $salon;
            });
        } catch (Throwable $e) {
            Log::error('BeautyService.createSalon failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function bookService(int $salonId, array $booking): array
    {
        return DB::transaction(function () use ($salonId, $booking) {
            return ['booking_id' => Str::uuid(), 'status' => 'pending'];
        });
    }

    public function updateSchedule(BeautySalon $salon, array $schedule): BeautySalon
    {
        return DB::transaction(function () use ($salon, $schedule) {
            $salon->update($schedule);
            return $salon;
        });
    }
}
