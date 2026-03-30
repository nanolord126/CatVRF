<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ActivityController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    userId} - Get user activity
     * - POST /api/v2/activities/track - Track activity
     *
     * @package App\Http\Controllers\Api\V2
     */
    final class ActivityController extends BaseApiV2Controller
    {
        public function __construct(
            private readonly UserActivityService $activityService,
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
            $tenantId = filament()->getTenant()?->id ?? auth()->user()?->tenant_id;
            try {
                $activities = []; // In production: fetch from database
                return $this->successResponse(
                    data: $activities,
                    message: 'Activities retrieved',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to get activities', [
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
                    userId: auth()->id() ?? 0,
                    tenantId: filament()->getTenant()?->id ?? 0,
                    activity: request()->get('activity', 'unknown'),
                    metadata: request()->get('metadata', [])
                );
                return $this->successResponse(
                    data: ['tracked' => true],
                    message: 'Activity tracked',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to track activity', [
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
