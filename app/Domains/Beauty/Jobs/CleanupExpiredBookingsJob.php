<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CleanupExpiredBookingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $expired = Appointment::query()
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subMinutes(15))
                ->update(['status' => 'expired']);

            Log::channel('audit')->info('Expired bookings cleaned', [
                'count' => $expired,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }
}
