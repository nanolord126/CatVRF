<?php

namespace App\Services\AI\Communications;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmartNotificationEngine
{
    /**
     * Determine the optimal time to send notification based on historical interaction logs.
     * Uses patterns from ConsumerBehaviorLog (Simulated).
     */
    public function calculateOptimalSendTime(User $user, string $triggerContext): \DateTime
    {
        // 1. Get average interaction hour for the user from logs
        $avgHour = DB::table('consumer_behavior_logs')
            ->where('user_id', $user->id)
            ->select(DB::raw('HOUR(created_at) as h'))
            ->groupBy('h')
            ->orderByDesc(DB::raw('count(*)'))
            ->first();

        $targetHour = $avgHour ? (int)$avgHour->h : 18; // Default 6 PM

        // 2. Adjust based on Trigger Context
        // If it's a Food promo, target 11 AM (Lunch) or 6 PM (Dinner)
        if ($triggerContext === 'food_delivery') {
            $targetHour = now()->hour < 11 ? 11 : 18;
        }

        // 3. Construct DateTime (Today or Tomorrow)
        $scheduledTime = now()->setTime($targetHour, 0);
        if ($scheduledTime->isPast()) {
            $scheduledTime->addDay();
        }

        return $scheduledTime;
    }

    /**
     * Dispatch smart, context-aware message.
     */
    public function queueSmartNotification(User $user, string $title, string $message, string $context): void
    {
        $sendAt = $this->calculateOptimalSendTime($user, $context);

        DB::table('smart_notifications')->insert([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'channel' => 'push',
            'trigger_context' => $context,
            'urgency_score' => $context === 'emergency' ? 1.0 : 0.5,
            'scheduled_send_at' => $sendAt,
            'correlation_id' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
