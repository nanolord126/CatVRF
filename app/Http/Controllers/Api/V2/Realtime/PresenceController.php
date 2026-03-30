<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Realtime;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PresenceController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly RealtimeService $realtimeService,
        ) {
            parent::__construct();
        }
        /**
         * Track user presence
         * POST /api/v2/realtime/presence
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function track(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            $tenantId = filament()->getTenant()?->id ?? auth()->user()?->tenant_id;
            try {
                $request->validate([
                    'status' => 'required|in:online,away,busy',
                    'location' => 'nullable|string|max:255',
                ]);
                $this->realtimeService->trackPresence(
                    userId: auth()->id() ?? 0,
                    tenantId: $tenantId,
                    data: [
                        'status' => $request->get('status'),
                        'location' => $request->get('location'),
                    ]
                );
                Log::channel('audit')->info('Presence tracked', [
                    'user_id' => auth()->id(),
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse(
                    data: [
                        'status' => 'tracked',
                        'user_id' => auth()->id(),
                    ],
                    message: 'Presence tracked successfully',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to track presence', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->errorResponse(
                    message: 'Failed to track presence',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
        /**
         * Get online users
         * GET /api/v2/realtime/online
         *
         * @return JsonResponse
         */
        public function getOnline(): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            $tenantId = filament()->getTenant()?->id ?? auth()->user()?->tenant_id;
            try {
                $onlineUsers = $this->realtimeService->getOnlineUsers($tenantId);
                Log::channel('audit')->info('Online users retrieved', [
                    'tenant_id' => $tenantId,
                    'count' => count($onlineUsers),
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse(
                    data: [
                        'online_users' => $onlineUsers,
                        'count' => count($onlineUsers),
                    ],
                    message: 'Online users retrieved',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to get online users', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to retrieve online users',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
        /**
         * Stop tracking presence
         * DELETE /api/v2/realtime/presence
         *
         * @return JsonResponse
         */
        public function stopTracking(): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                cache()->forget("presence:user." . auth()->id());
                Log::channel('audit')->info('Presence tracking stopped', [
                    'user_id' => auth()->id(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse(
                    data: ['status' => 'stopped'],
                    message: 'Presence tracking stopped',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to stop tracking presence', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to stop tracking',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
}
