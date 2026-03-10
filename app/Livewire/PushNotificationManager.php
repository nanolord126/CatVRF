<?php
namespace App\Livewire;
use Livewire\Component;
use App\Models\PushSubscription;

class PushNotificationManager extends Component {
    public function subscribe($endpoint, $keys) {
        $user = auth('tenant')->user() ?? auth()->user();
        if ($user) {
            $user->updatePushSubscription($endpoint, $keys['p256dh'], $keys['auth']);
        }
        $this->dispatch('push-subscribed');
    }
    public function render() {
        return <<<'HTML'
        <div x-data="{ permission: Notification.permission }" @notification-permission-change.window="permission = Notification.permission">
            <template x-if="permission !== 'granted'">
                <button @click="Notification.requestPermission()" class="fixed bottom-24 right-6 p-4 bg-blue-600 text-white rounded-full shadow-2xl animate-pulse">
                    🔔 Enable Alerts
                </button>
            </template>
        </div>
        HTML;
    }
}
