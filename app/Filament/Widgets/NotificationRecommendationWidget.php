<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\NotificationRecommendationService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

final class NotificationRecommendationWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-recommendation-widget';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '300s';

    public function __construct(
        private readonly NotificationRecommendationService $recommendationService,
    ) {}

    public function getViewData(): array
    {
        $userId = Auth::id();
        $tenantId = Auth::user()?->tenant_id ?? 0;

        return [
            'recommendations' => [
                'channels' => $this->recommendationService->getRecommendedChannels($userId, $tenantId),
                'types' => $this->recommendationService->getRecommendedTypes($userId, $tenantId, 5),
                'optimal_time' => $this->recommendationService->getOptimalSendTime($userId, $tenantId),
                'personalization' => $this->recommendationService->getPersonalizationSuggestions($userId, $tenantId),
            ],
        ];
    }
}
