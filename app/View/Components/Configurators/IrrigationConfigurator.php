<?php

declare(strict_types=1);

namespace App\View\Components\Configurators;

use Illuminate\View\Component;
use Illuminate\View\View;

final class IrrigationConfigurator extends Component
{
    public function __construct(
        public string $uuid = 'irrigation-x-flow',
        public array $options = []
    ) {}

    public function render(): View
    {
        return view('components.configurators.irrigation-configurator');
    }
}
