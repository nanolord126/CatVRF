<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncOfflineOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public \App\Models\OfflineSync $sync) {}
    public function handle(\App\Services\OfflineSyncService $service): void
    {
        $service->sync($this->sync);
    }
}
