<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class CleanupExpiredVideoCallsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        $expiredCalls = DB::table('auto_repair_orders')
            ->where('metadata->video_call_expires_at', '<=', now()->toIso8601String())
            ->whereNotNull('metadata->webrtc_room_id')
            ->get();

        $cleanedCount = 0;

        foreach ($expiredCalls as $call) {
            $metadata = json_decode($call->metadata ?? '{}', true);
            unset($metadata['webrtc_room_id'], $metadata['webrtc_token'], $metadata['video_call_expires_at']);

            DB::table('auto_repair_orders')
                ->where('id', $call->id)
                ->update([
                    'metadata' => json_encode($metadata),
                    'updated_at' => now(),
                ]);

            $cleanedCount++;
        }

        Log::channel('audit')->info('auto.video_calls.cleanup.completed', [
            'cleaned_count' => $cleanedCount,
        ]);
    }
}
