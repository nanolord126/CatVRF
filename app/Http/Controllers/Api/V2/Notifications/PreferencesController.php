<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PreferencesController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    channel}
     * - POST /api/v2/notifications/do-not-disturb
     * - DELETE /api/v2/notifications/do-not-disturb
     *
     * @package App\Http\Controllers\Api\V2\Notifications
     */
    final class PreferencesController extends BaseApiV2Controller
    {
        public function __construct(
            private readonly NotificationPreferencesService $preferencesService,
        ) {
            parent::__construct();
        }
        /**
         * Get notification preferences
         * GET /api/v2/notifications/preferences
         *
         * @return JsonResponse
         */
        public function get(): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $preferences = $this->preferencesService->getPreferences(auth()->id() ?? 0);
                return $this->successResponse(
                    data: $preferences,
                    message: 'Preferences retrieved',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to get preferences', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to retrieve preferences',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
        /**
         * Update channel preferences
         * PUT /api/v2/notifications/preferences/{channel}
         *
         * @param Request $request
         * @param string $channel
         * @return JsonResponse
         */
        public function updateChannel(Request $request, string $channel): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $request->validate([
                    'enabled' => 'boolean',
                    'categories' => 'array',
                    'categories.*' => 'boolean',
                ]);
                $validated = $request->validate([
                    'marketing' => 'nullable|boolean',
                    'orders' => 'nullable|boolean',
                    'system' => 'nullable|boolean',
                    'reminders' => 'nullable|boolean'
                ]);
                $this->preferencesService->updateChannelPreferences(
                    userId: auth()->id() ?? 0,
                    channel: $channel,
                    preferences: $validated
                );
                return $this->successResponse(
                    data: ['channel' => $channel, 'updated' => true],
                    message: 'Preferences updated',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to update channel preferences', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to update preferences',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
        /**
         * Set do-not-disturb mode
         * POST /api/v2/notifications/do-not-disturb
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function setDND(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $request->validate([
                    'start_time' => 'required|date_format:H:i',
                    'end_time' => 'required|date_format:H:i',
                ]);
                $this->preferencesService->setDoNotDisturb(
                    userId: auth()->id() ?? 0,
                    startTime: $request->get('start_time'),
                    endTime: $request->get('end_time')
                );
                return $this->successResponse(
                    data: ['dnd_enabled' => true],
                    message: 'Do-not-disturb enabled',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to set DND', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to enable do-not-disturb',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
        /**
         * Disable do-not-disturb mode
         * DELETE /api/v2/notifications/do-not-disturb
         *
         * @return JsonResponse
         */
        public function disableDND(): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $this->preferencesService->disableDoNotDisturb(auth()->id() ?? 0);
                return $this->successResponse(
                    data: ['dnd_enabled' => false],
                    message: 'Do-not-disturb disabled',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to disable DND', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to disable do-not-disturb',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
}
