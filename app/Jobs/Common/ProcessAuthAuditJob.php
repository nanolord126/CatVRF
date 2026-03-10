<?php

namespace App\Jobs\Common;

use App\Models\ActiveDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAuthAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        ActiveDevice::updateOrCreate(
            ['user_id' => $this->data['user_id'], 'session_id' => $this->data['session_id']],
            [
                'ip' => $this->data['ip'],
                'user_agent' => $this->data['user_agent'],
                'last_active_at' => now(),
                'browser' => $this->data['browser'] ?? 'Unknown',
            ]
        );
    }
}
