<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\CooldownActionType;
use App\Models\CooldownPeriod;
use App\Services\Security\CooldownService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Cooldown Status Widget
 * 
 * Displays active cooldowns for the current user/tenant.
 * Shows remaining time and action types.
 */
final class CooldownStatusWidget extends Component
{
    public array $activeCooldowns = [];

    public function mount(CooldownService $cooldownService): void
    {
        $user = Auth::user();
        
        if ($user === null) {
            return;
        }

        $cooldowns = $cooldownService->getActiveCooldownsForUser($user);

        $this->activeCooldowns = $cooldowns->map(function (CooldownPeriod $cooldown) {
            return [
                'id' => $cooldown->id,
                'action_type' => $cooldown->action_type,
                'action_label' => $cooldown->getActionTypeEnum()->getLabel(),
                'reason' => $cooldown->reason,
                'triggered_at' => $cooldown->triggered_at->toIso8601String(),
                'expires_at' => $cooldown->expires_at->toIso8601String(),
                'remaining_seconds' => $cooldown->getRemainingSeconds(),
                'remaining_time' => $cooldown->getRemainingTimeForHumans(),
                'is_user_level' => $cooldown->user_id === Auth::id(),
                'is_tenant_level' => $cooldown->tenant_id !== null,
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.cooldown-status-widget');
    }
}
