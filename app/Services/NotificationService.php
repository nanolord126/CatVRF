<?php
namespace App\Services;

use Illuminate\Support\Carbon;

class NotificationService {
    public static function send($user, $title, $body, $type = 'info') {
        $user->notify(new \App\Notifications\GenericNotification($title, $body, $type));
        event(new \App\Events\InAppNotification($user->id, [
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'timestamp' => Carbon::now()
        ]));
    }
}
