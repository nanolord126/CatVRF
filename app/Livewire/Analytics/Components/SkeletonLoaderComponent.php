<?php declare(strict_types=1);

namespace App\Livewire\Analytics\Components;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SkeletonLoaderComponent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public bool $isLoading = true;
        public int $lines = 5; // Количество линий в скелете

        public function render()
        {
            return view('livewire.analytics.components.skeleton-loader-component', [
                'displayedLines' => range(1, $this->lines),
            ]);
        }
}
