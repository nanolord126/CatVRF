<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use App\Services\Security\UserDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;

final class DeviceManagementController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly UserDeviceService $userDeviceService,
        private readonly ValidationFactory $validator,
    ) {}

    /**
     * List user devices
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->auth->user();

        $devices = $this->userDeviceService->getUserDevices($user);

        return new JsonResponse([
            'success' => true,
            'devices' => $devices->map(fn ($device) => [
                'id' => $device->id,
                'device_name' => $device->device_name,
                'device_type' => $device->device_type,
                'platform' => $device->platform,
                'browser' => $device->browser,
                'is_trusted' => $device->is_trusted,
                'is_current' => $device->is_current,
                'is_revoked' => $device->is_revoked,
                'first_seen_at' => $device->first_seen_at?->toIso8601String(),
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                'auth_count' => $device->auth_count,
                'location_country' => $device->location_country,
                'location_city' => $device->location_city,
            ]),
        ]);
    }

    /**
     * Revoke a device
     */
    public function revoke(Request $request, string $deviceId): JsonResponse
    {
        $user = $this->auth->user();

        $this->userDeviceService->revokeDevice($user, $deviceId);

        return new JsonResponse([
            'success' => true,
            'message' => 'Device revoked successfully',
        ]);
    }

    /**
     * Trust a device
     */
    public function trust(Request $request, string $deviceId): JsonResponse
    {
        $user = $this->auth->user();

        $this->userDeviceService->trustDevice($user, $deviceId);

        return new JsonResponse([
            'success' => true,
            'message' => 'Device trusted successfully',
        ]);
    }

    /**
     * Revoke all other devices
     */
    public function revokeAllOthers(Request $request): JsonResponse
    {
        $user = $this->auth->user();

        $validator = $this->validator->make($request->all(), [
            'current_device_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->userDeviceService->revokeAllOtherDevices($user, $request->current_device_id);

        return new JsonResponse([
            'success' => true,
            'message' => 'All other devices revoked successfully',
        ]);
    }

    /**
     * Get current device info
     */
    public function current(Request $request): JsonResponse
    {
        $user = $this->auth->user();
        $fingerprint = $request->header('X-Device-Fingerprint') ?? $request->ip();

        $device = UserDevice::where('user_id', $user->id)
            ->where('fingerprint', $fingerprint)
            ->first();

        if (! $device) {
            return new JsonResponse([
                'success' => true,
                'is_new' => true,
                'device' => null,
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'is_new' => false,
            'device' => [
                'id' => $device->id,
                'device_name' => $device->device_name,
                'device_type' => $device->device_type,
                'is_trusted' => $device->is_trusted,
                'is_current' => $device->is_current,
                'auth_count' => $device->auth_count,
                'days_since_first_seen' => $device->daysSinceFirstSeen(),
            ],
        ]);
    }
}
