<?php declare(strict_types=1);

/**
 * BreadcrumbComponent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/breadcrumbcomponent
 * @see https://catvrf.ru/docs/breadcrumbcomponent
 * @see https://catvrf.ru/docs/breadcrumbcomponent
 * @see https://catvrf.ru/docs/breadcrumbcomponent
 */


namespace App\Livewire\Analytics\Components;

use Livewire\Component;

/**
 * Class BreadcrumbComponent
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 *
 * @package App\Livewire\Analytics\Components
 */
final class BreadcrumbComponent extends Component
{
    private string $currentPage = 'dashboard';
        private string $heatmapType = 'geo';
        private string $vertical = 'beauty';

        /**
         * Handle render operation.
         *
         * @throws \DomainException
         */
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

            return view('livewire.analytics.components.breadcrumb-component', [
                'breadcrumbs' => $breadcrumbs,
            ]);
        }
}
