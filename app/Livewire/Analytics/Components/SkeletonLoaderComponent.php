<?php

declare(strict_types=1);

namespace App\Livewire\Analytics\Components;

use Livewire\Component;

/**
 * Компонент: Skeleton Loading для графиков
 * 
 * Отображает плейсхолдер во время загрузки данных
 * Плавное исчезновение при готовности
 */
final class SkeletonLoaderComponent extends Component
{
    public bool $isLoading = true;
    public int $lines = 5; // Количество линий в скелете

    public function render()
    {
        return view('livewire.analytics.components.skeleton-loader-component', [
            'displayedLines' => range(1, $this->lines),
        ]);
    }
}
