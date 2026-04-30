<?php

declare(strict_types=1);

namespace App\Http\Livewire\Security;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Services\Security\UserDeviceService;
use Illuminate\Auth\AuthManager;
use Livewire\Component;

final class DeviceManagement extends Component
{
    public $devices;

    public $currentDeviceId;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(UserDeviceService $deviceService)
    {
        $user = $this->auth->user();
        $this->devices = $deviceService->getUserDevices($user);
        $this->currentDeviceId = $this->devices->firstWhere('is_current')?->id;
    }

    public function revokeDevice(string $deviceId, UserDeviceService $deviceService)
    {
        $user = $this->auth->user();
        $deviceService->revokeDevice($user, $deviceId);

        $this->devices = $deviceService->getUserDevices($user);
        $this->dispatch('device-revoked');
    }

    public function trustDevice(string $deviceId, UserDeviceService $deviceService)
    {
        $user = $this->auth->user();
        $deviceService->trustDevice($user, $deviceId);

        $this->devices = $deviceService->getUserDevices($user);
        $this->dispatch('device-trusted');
    }

    public function revokeAllOthers(UserDeviceService $deviceService)
    {
        $user = $this->auth->user();
        $deviceService->revokeAllOtherDevices($user, $this->currentDeviceId);

        $this->devices = $deviceService->getUserDevices($user);
        $this->dispatch('all-devices-revoked');
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.security.device-management');
    }
}
