declare(strict_types=1);

<?php

namespace Modules\Analytics\Services;

use Modules\Analytics\Models\BehavioralEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * BehavioralTracker
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BehavioralTracker
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function capture(
        string $eventType,
        string $vertical,
        ?string $targetId = null,
        array $payload = [],
        float $monetaryValue = 0.0
    ): void {
        $userId = Auth::id();
        if (!$userId) return;

        // В идеале это должно отправляться в Queue для High Performance
        BehavioralEvent::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'vertical' => $vertical,
            'target_id' => $targetId,
            'payload' => $payload,
            'monetary_value' => $monetaryValue,
            'correlation_id' => session()->get('correlation_id', Str::uuid()->toString()),
            'occurred_at' => now(),
        ]);
    }
}
