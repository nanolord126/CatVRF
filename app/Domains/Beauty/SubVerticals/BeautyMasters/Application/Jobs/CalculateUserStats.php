<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\BeautyMasters\Infrastructure\Models\AppointmentModel;
use Modules\BeautyMasters\Infrastructure\Models\ClientModel;

final class CalculateUserStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $userId,
        public readonly int $venueId,
    ) {
    }

    public function handle(): void
    {
        try {
            $stats = AppointmentModel::where('client_id', $this->userId)
                ->where('venue_id', $this->venueId)
                ->where('status', 'completed')
                ->select([
                    DB::raw('COUNT(*) as total_visits'),
                    DB::raw('SUM(final_price) as total_spent'),
                    DB::raw('AVG(final_price) as average_check'),
                    DB::raw('MIN(start_time) as first_visit_at'),
                    DB::raw('MAX(start_time) as last_visit_at'),
                ])
                ->first();

            ClientModel::where('id', $this->userId)
                ->where('venue_id', $this->venueId)
                ->update([
                    'total_visits' => $stats->total_visits ?? 0,
                    'total_spent' => $stats->total_spent ?? 0,
                    'average_check' => $stats->average_check ?? 0,
                    'first_visit_at' => $stats->first_visit_at,
                    'last_visit_at' => $stats->last_visit_at,
                ]);

            Log::info('User stats calculated', [
                'user_id' => $this->userId,
                'venue_id' => $this->venueId,
                'total_visits' => $stats->total_visits ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to calculate user stats', [
                'user_id' => $this->userId,
                'venue_id' => $this->venueId,
                'error' => $e->getMessage(),
            ]);
            $this->release(120);
        }
    }
}
