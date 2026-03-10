<?php

namespace App\Observers;

use App\Models\ActiveDevice;
use Illuminate\Support\Facades\Request;

class AuthObserver
{
    public function authenticated($user)
    {
        $sessionId = session()->getId();
        $ip = Request::ip();
        $ua = Request::userAgent();

        $deviceExists = ActiveDevice::where('user_id', $user->id)
            ->where('user_agent', $ua)
            ->exists();

        if (!$deviceExists) {
            $user->notify(new \App\Notifications\NewDeviceDetectedNotification($ip, $ua));
        }

        \App\Jobs\Common\ProcessAuthAuditJob::dispatch([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip' => $ip,
            'user_agent' => $ua,
            'browser' => parse_user_agent($ua)->browser ?? 'Unknown',
        ]);
    }
}
