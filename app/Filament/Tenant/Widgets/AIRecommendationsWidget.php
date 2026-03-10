<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Common\AI\RecommendationService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * 2026 AI Hybrid Dashboard Widget.
 * Shows personalized recommendations and AI-assisted search trends for the Business Owner.
 */
class AIRecommendationsWidget extends Widget
{
    /**
     * @var string
     */
    protected static string $view = 'filament.tenant.widgets.ai-recommendations-widget';

    /**
     * @var int
     */
    protected int $limit = 5;

    /**
     * Get data for recommendations based on User History + Tenant Context.
     */
    public function getRecommendations(): array
    {
        /** @var RecommendationService $recService */
        $recService = app(RecommendationService::class);
        $user = Auth::user();

        // Specific to business-to-business or tenant-to-tenant recommendations (Supplier/Partner)
        // If the context is the tenant dashboard, recommend B2B Partners/Opportunities
        return $recService->forUser($user, $this->limit);
    }

    /**
     * Pass dynamic data to the view.
     */
    protected function getViewData(): array
    {
        return [
            'recommendations' => $this->getRecommendations(),
            'ai_title' => 'AI Ecosystem Recommendations (2026)',
            'ai_status' => 'Hybrid Search Engine: Online (Typesense/OpenAI)'
        ];
    }
}
