<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use Psr\Log\LoggerInterface;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

final class DeviceController extends Controller
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly AuthService $auth,) {}

    /**
     * Get user devices
     * GET /api/v1/auth/devices
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        $devices = $this->auth->getUserDevices($user);

        return new JsonResponse([
            'devices' => $devices->map(fn ($device) => [
                'id' => $device->id,
                'device_name' => $device->device_name,
                'device_type' => $device->device_type,
                'last_used_at' => $device->last_used_at,
                'is_trusted' => $device->is_trusted,
                'location_country' => $device->location_country,
                'location_city' => $device->location_city,
            ]),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Revoke device
     * DELETE /api/v1/auth/devices/{device}
     */
    public function destroy(Request $request, int $deviceId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        $this->auth->revokeDevice($user, $deviceId);

        $this->log->channel('audit')->$this->logger->info('Device revoked', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'message' => 'Device revoked successfully',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Revoke all devices except current
     * POST /api/v1/auth/devices/revoke-others
     */
    public function revokeOthers(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        $currentDeviceId = $request->input('current_device_id');

        if (! $currentDeviceId) {
            return new JsonResponse([
                'error' => 'current_device_id is required',
                'correlation_id' => $correlationId,
            ], 400);
        }

        $user->revokeOtherDevices($currentDeviceId);

        $this->log->channel('audit')->$this->logger->info('Other devices revoked', [
            'user_id' => $user->id,
            'current_device_id' => $currentDeviceId,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'message' => 'Other devices revoked successfully',
            'correlation_id' => $correlationId,
        ]);
    }
}
