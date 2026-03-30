<?php declare(strict_types=1);

namespace App\View\Components\Configurators;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class IrrigationConfigurator extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public string $uuid = 'irrigation-x-flow',
            public array $options = []
        ) {}

        public function render(): View
        {
            return view('components.configurators.irrigation-configurator');
        }
}
