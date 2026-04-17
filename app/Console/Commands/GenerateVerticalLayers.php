<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\VerticalGeneratorService;
use Illuminate\Console\Command;

final class GenerateVerticalLayers extends Command
{
    protected $signature = 'vertical:generate {vertical_name} {vertical_slug}';
    protected $description = 'Generate 9-layer architecture for a vertical';

    public function __construct(
        private VerticalGeneratorService $generator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $verticalName = $this->argument('vertical_name');
        $verticalSlug = $this->argument('vertical_slug');

        $this->info("Generating 9-layer architecture for {$verticalName}...");

        $this->generator->generateVertical($verticalName, $verticalSlug);

        $this->info("✓ Generated all 9 layers for {$verticalName}");

        return Command::SUCCESS;
    }
}
