<?php declare(strict_types=1);

namespace App\View\Components\Configurators;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SolarTreeConfigurator extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public string $uuid = 'solar-tree-99-neo',
            public array $options = []
        ) {}

        public function render(): View
        {
            return view('components.configurators.solar-tree-configurator');
        }
}
