<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Services\Analytics\ConsumerBehaviorAIService;
use App\Models\User;

/**
 * Universal Observer to capture user behavior for AI training on any model change.
 */
class EcosystemBehaviorObserver
{
    public function __construct(protected ConsumerBehaviorAIService $aiService) {}

    /**
     * Listen to Created, Updated, and Deleted events to feed the AI Brain.
     */
    public function created(Model $model): void
    {
        $this->logActivity($model, 'create');
    }

    public function updated(Model $model): void
    {
        $this->logActivity($model, 'update');
    }

    private function logActivity(Model $model, string $action): void
    {
        // Ignore internal logs or non-user contexts
        if ($model instanceof \App\Models\Analytics\ConsumerBehaviorLog) return;

        $user = auth('tenant')->user() ?? auth()->user() ?? ($model->user ?? null);
        
        if ($user instanceof User) {
            $this->aiService->logEvent(
                $user,
                "model_{$action}",
                get_class($model),
                $model->id,
                ['changes' => $model->getChanges()]
            );
        }
    }
}
