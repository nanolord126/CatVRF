<?php declare(strict_types=1);

namespace App\Livewire\Analytics\Components;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BreadcrumbComponent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public string $currentPage = 'dashboard';
        public string $heatmapType = 'geo';
        public string $vertical = 'beauty';

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
