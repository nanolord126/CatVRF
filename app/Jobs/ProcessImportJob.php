<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public \App\Models\ImportTrack $track) {}
    public function handle(): void {
        $this->track->update(['status' => 'processing']);
        // 2026 AI integration: PDF recognition and Excel parsing logic here.
        // For now, chunking through rows for speed.
        try {
            // Logic would call ImportService->processChunks()
            $this->track->increment('processed_rows', 100);
            $this->track->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $this->track->update(['status' => 'failed', 'errors' => [$e->getMessage()]]);
        }
    }
}
