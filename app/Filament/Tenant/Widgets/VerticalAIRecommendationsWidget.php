<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;
use App\Services\Common\AI\RecommendationServiceVertical;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class VerticalAIRecommendationsWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.vertical-ai-recommendations-widget';

    public ?string $vertical = null;
    public ?int $tenantId = null;

    /**
     * Get tailored recommendations for the current business.
     */
    protected function getViewData(): array
    {
        $tenant = Tenant::find($this->tenantId ?: session('tenant_id'));
        $service = app(RecommendationServiceVertical::class);

        return [
            'recommendations' => $service->forVertical($tenant, $this->vertical ?: $tenant->vertical),
            'crossRecommendations' => $service->crossVertical($tenant),
            'budget' => $tenant->wallet->balance ?? 0,
            'correlation_id' => $service->getCorrelationId()
        ];
    }

    /**
     * Smart Purchase logic: generates pre-filled order + invoice.
     */
    public function buyNowAction(): Action
    {
        return Action::make('buyNow')
            ->label('Smart Buy (1-Click)')
            ->icon('heroicon-o-shopping-bag')
            ->color('success')
            ->action(function (array $arguments) {
                // Pre-fill B2BOrder via Procurement Service
                $orderId = app(\App\Services\B2B\B2BProcurementService::class)->createSmartOrder([
                    'product_id' => $arguments['productId'],
                    'tenant_id' => session('tenant_id'),
                    'source' => 'AI_RECOMMENDATION'
                ]);

                Notification::make()
                    ->title('Draft Order Created')
                    ->body("AI pre-filled a B2BOrder. View in Procurement Panel.")
                    ->success()
                    ->send();
            });
    }
}
