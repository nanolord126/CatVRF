<?php
namespace App\Livewire;
use Livewire\Component;
use App\Models\OfflineSync;
use App\Services\OfflineSyncService;

class OfflineFormHandler extends Component {
    public $payload = [];
    public $model;

    public function submit() {
        $service = new OfflineSyncService();
        $sync = $service->stage($this->model, $this->payload, auth()->id());
        $this->dispatch('sync-staged', correlation_id: $sync->correlation_id);
    }
    public function render() {
        return <<<'HTML'
        <div x-data="{ online: window.navigator.onLine }" @online.window="online = true" @offline.window="online = false">
            <div x-show="!online" class="p-4 bg-orange-500/10 text-orange-600 rounded-xl mb-4 border border-orange-500/20">
                You are currently offline. Your order will be saved and synced automatically when connection is restored.
            </div>
            <slot></slot>
        </div>
        HTML;
    }
}
