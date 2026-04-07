<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Infrastructure\Services;

use Illuminate\Support\Facades\Log;

/**
 * Сервис для интеграции с системой аналитики (например, ClickHouse или Mixpanel).
 */
final class AnalyticsIntegrationService
{
    public function trackEvent(string $eventName, array $properties): void
    {
        // Здесь будет логика отправки события в вашу систему аналитики.
        // Например, через Log::channel('analytics') или специальный SDK.
        Log::channel('audit')->info('Analytics Event Tracked', [
            'event_name' => $eventName,
            'properties' => $properties,
        ]);
    }
}
