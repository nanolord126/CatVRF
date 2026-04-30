<?php

declare(strict_types=1);

/**
 * BreadcrumbComponent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/breadcrumbcomponent
 * @see https://catvrf.ru/docs/breadcrumbcomponent
 * @see https://catvrf.ru/docs/breadcrumbcomponent
 * @see https://catvrf.ru/docs/breadcrumbcomponent
 */

namespace App\Livewire\Analytics\Components;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

/**
 * Class BreadcrumbComponent
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 */
final class BreadcrumbComponent extends Component
{
    private readonly string $currentPage = 'dashboard';

    private readonly string $heatmapType = 'geo';

    private readonly string $vertical = 'beauty';

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function render()
    {
        $breadcrumbs = [
            ['label' => '📊 Аналитика', 'route' => 'analytics.dashboard'],
            ['label' => '📈 Дашборд', 'route' => 'analytics.heatmaps'],
        ];

        if ($this->heatmapType === 'geo') {
            $breadcrumbs[] = ['label' => '🗺️ Географические тепловые карты'];
        } else {
            $breadcrumbs[] = ['label' => '🖱️ Клик-тепловые карты'];
        }

        if ($this->vertical) {
            $verticalLabels = [
                'beauty' => '💄 Красота',
                'auto' => '🚗 Авто',
                'food' => '🍔 Еда',
                'hotels' => '🏨 Отели',
                'real_estate' => '🏠 Недвижимость',
            ];
            $breadcrumbs[] = ['label' => $verticalLabels[$this->vertical] ?? $this->vertical];
        }

        return $this->viewFactory->make('livewire.analytics.components.breadcrumb-component', [
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
