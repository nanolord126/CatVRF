<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class ActivityController extends Controller
{
    public function __construct(
        private readonly Request $request,
            private readonly UserActivityService $activityService,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    ) {
            parent::__construct();
        }
        /**
         * Get recent activities
         * GET /api/v2/activities
         *
         * @return JsonResponse
         */
        public function getActivities(): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            $tenantId = filament()->getTenant()?->id ?? $this->guard->user()?->tenant_id;
            try {
                $activities = []; // In production: fetch from database
                return $this->successResponse(
                    data: $activities,
                    message: 'Activities retrieved',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to get activities', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to retrieve activities',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
        /**
         * Track user activity
         * POST /api/v2/activities/track
         *
         * @return JsonResponse
         */
        public function track(): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $this->activityService->recordActivity(
                    userId: $this->guard->id() ?? 0,
                    tenantId: filament()->getTenant()?->id ?? 0,
                    activity: $this->request->get('activity', 'unknown'),
                    metadata: $this->request->get('metadata', [])
                );
                return $this->successResponse(
                    data: ['tracked' => true],
                    message: 'Activity tracked',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to track activity', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to track activity',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
}
