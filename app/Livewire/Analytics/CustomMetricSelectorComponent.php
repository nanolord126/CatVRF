<?php declare(strict_types=1);

namespace App\Livewire\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CustomMetricSelectorComponent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public string $selectedMetric = 'event_intensity';
        public bool $isEnabled = false;

        protected $listeners = ['enable-custom-metric'];

        public function toggleEnabled(): void
        {
            $this->isEnabled = !$this->isEnabled;
            $this->dispatch('custom-metric-toggled', enabled: $this->isEnabled);
        }

        public function selectMetric(string $metric): void
        {
            $this->selectedMetric = $metric;
            $this->dispatch('custom-metric-selected', metric: $metric);
        }

        public function render()
        {
            return view('livewire.analytics.custom-metric-selector-component', [
                'geoMetrics' => [
                    'event_intensity' => [
                        'name' => 'Интенсивность событий',
                        'description' => 'Плотность событий по локациям',
                        'icon' => '🔥',
                    ],
                    'engagement_score' => [
                        'name' => 'Оценка вовлечённости',
                        'description' => 'Индекс взаимодействия пользователей',
                        'icon' => '💬',
                    ],
                    'growth_rate' => [
                        'name' => 'Темп роста',
                        'description' => 'Процентное изменение показателей',
                        'icon' => '📈',
                    ],
                    'hotspot_concentration' => [
                        'name' => 'Концентрация горячих точек',
                        'description' => 'Сосредоточение активности',
                        'icon' => '🎯',
                    ],
                    'user_retention' => [
                        'name' => 'Удержание пользователей',
                        'description' => 'Процент вернувшихся пользователей',
                        'icon' => '👥',
                    ],
                ],
                'clickMetrics' => [
                    'click_density' => [
                        'name' => 'Плотность кликов',
                        'description' => 'Кол-во кликов на единицу площади',
                        'icon' => '🖱️',
                    ],
                    'interaction_score' => [
                        'name' => 'Оценка взаимодействия',
                        'description' => 'Индекс кликов пользователей',
                        'icon' => '⚡',
                    ],
                    'user_engagement' => [
                        'name' => 'Вовлечённость пользователей',
                        'description' => 'Среднее кликов на пользователя',
                        'icon' => '🎪',
                    ],
                    'click_conversion' => [
                        'name' => 'Конверсия по кликам',
                        'description' => 'Процент кликовавших пользователей',
                        'icon' => '🔗',
                    ],
                ],
            ]);
        }
}
