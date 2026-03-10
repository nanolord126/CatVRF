<?php
namespace Modules\Advertising\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Advertising\Models\Creative;
use Modules\Advertising\Services\OrdService;

class SendToOrdJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3; public $backoff = 60;

    public function __construct(protected Creative $creative) {}

    public function handle(OrdService $ord): void {
        if ($erid = $ord->getErid($this->creative)) {
            $this->creative->update(['erid' => $erid]);
        }
    }
}
