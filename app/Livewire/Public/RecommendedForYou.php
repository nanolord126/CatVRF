<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Services\Common\AI\RecommendationService;
use App\Models\Common\AiUserTelemetry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

/**
 * 2026 AI Recommendations: Dynamic vertical carousel with similarity score tracking.
 */
class RecommendedForYou extends Component
{
    public array $recommendations = [];
    public bool $loading = true;
    public string $correlationId;

    protected $listeners = ['telemetryUpdate' => 'refreshRecommendations'];

    public function mount()
    {
        $this->correlationId = Context::get('correlation_id') ?? (string) Str::uuid();
        $this->loadRecommendations();
    }

    public function loadRecommendations()
    {
        $this->loading = true;
        
        /** @var RecommendationService $service */
        $service = app(\App\Services\Common\AI\RecommendationService::class);
        
        $user = Auth::user();
        
        // If logged in - use personalized, else use trending (mocked in service via context)
        $this->recommendations = $user 
            ? $service->forUser($user, 6) 
            : $service->geoNearby(55.7558, 37.6173, 10000, 6); // Default Moscow center for guests

        $this->loading = false;
    }

    public function trackInteraction(string $entityType, int $entityId, string $category)
    {
        if (Auth::check()) {
            AiUserTelemetry::create([
                'user_id' => Auth::id(),
                'event_type' => 'recommendation_click',
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'category' => $category,
                'correlation_id' => $this->correlationId,
                'payload' => [
                    'source' => 'RecommendedForYou_Widget',
                    'timestamp' => now()->toISOString()
                ]
            ]);
        }

        // Trigger global telemetry event for real-time reactivity
        $this->dispatch('telemetryUpdate');
        
        // Return or redirect based on entity (simple implementation for now)
        // return redirect()->to("/marketplace/{$category}/{$entityId}");
    }

    public function refreshRecommendations()
    {
        $this->loadRecommendations();
    }

    public function render()
    {
        return view('livewire.public.recommended-for-you');
    }
}
