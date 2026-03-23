<?php

declare(strict_types=1);

namespace App\View\Components\Configurators;

use Illuminate\View\Component;
use Illuminate\View\View;

final class GreenhouseConfigurator extends Component
{
    public function __construct(
        public string $uuid = 'gh-889-core',
        public array $options = []
    ) {}

    public function render(): View
    {
        return view('components.configurators.greenhouse-configurator');
    }
}
