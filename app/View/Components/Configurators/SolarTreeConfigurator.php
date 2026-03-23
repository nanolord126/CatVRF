<?php

declare(strict_types=1);

namespace App\View\Components\Configurators;

use Illuminate\View\Component;
use Illuminate\View\View;

final class SolarTreeConfigurator extends Component
{
    public function __construct(
        public string $uuid = 'solar-tree-99-neo',
        public array $options = []
    ) {}

    public function render(): View
    {
        return view('components.configurators.solar-tree-configurator');
    }
}
