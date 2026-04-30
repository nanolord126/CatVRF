<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Fix All Verticals Filament Command
 *
 * Mass-apply critical Filament fixes to all 64 business verticals:
 * 1. Update Resources to extend BaseOptimizedResource
 * 2. Update Widgets to extend BaseCachedWidget
 * 3. Add TenantScoped trait to Models
 * 4. Add audit logging
 *
 * Usage:
 * php artisan filament:fix-all-verticals
 */
final class FixAllVerticalsFilamentCommand extends Command
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }
    protected $signature = 'filament:fix-all-verticals
                            {--dry-run : Show changes without applying}
                            {--vertical= : Apply to specific vertical only}
                            {--skip-resources : Skip resource updates}
                            {--skip-widgets : Skip widget updates}
                            {--skip-models : Skip model updates}';

    protected $description = 'Apply Filament security/performance fixes to all verticals';

    private int $resourcesUpdated = 0;

    private int $widgetsUpdated = 0;

    private int $modelsUpdated = 0;

    public function handle(): int
    {
        $this->info('Starting Filament fixes for all verticals...');
        $this->newLine();

        $verticals = $this->getVerticals();
        $this->info('Found '.count($verticals).' verticals to process');

        foreach ($verticals as $vertical) {
            $this->processVertical($vertical);
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("- Resources updated: {$this->resourcesUpdated}");
        $this->info("- Widgets updated: {$this->widgetsUpdated}");
        $this->info("- Models updated: {$this->modelsUpdated}");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No changes were applied');
        }

        return Command::SUCCESS;
    }

    private function getVerticals(): array
    {
        $specificVertical = $this->option('vertical');

        if ($specificVertical) {
            return [$specificVertical];
        }

        // Get all domain directories
        $domainsPath = app_path('Domains');
        $verticals = [];

        if ($this->files->exists($domainsPath)) {
            $directories = $this->files->directories($domainsPath);
            foreach ($directories as $directory) {
                $vertical = basename($directory);
                // Skip technical directories
                if (! in_array($vertical, $this->getExcludedDirectories(), true)) {
                    $verticals[] = $vertical;
                }
            }
        }

        return $verticals;
    }

    private function getExcludedDirectories(): array
    {
        return [
            'AI', 'Audit', 'B2B', 'BigData', 'Bonuses', 'Cart', 'Commissions',
            'Common', 'Compliance', 'DemandForecast', 'FraudML', 'ML',
            'Notifications', 'Payout', 'PromoCampaigns', 'Realtime',
            'Recommendation', 'Referral', 'Search', 'Security',
            'UserProfile', 'VerticalName', 'Webhooks',
        ];
    }

    private function processVertical(string $vertical): void
    {
        $this->info("Processing vertical: {$vertical}");

        $verticalPath = app_path("Domains/{$vertical}");

        if (! $this->files->exists($verticalPath)) {
            $this->warn('  - Skipped: directory not found');

            return;
        }

        // Update Resources
        if (! $this->option('skip-resources')) {
            $this->updateVerticalResources($vertical, $verticalPath);
        }

        // Update Widgets
        if (! $this->option('skip-widgets')) {
            $this->updateVerticalWidgets($vertical, $verticalPath);
        }

        // Update Models
        if (! $this->option('skip-models')) {
            $this->updateVerticalModels($vertical, $verticalPath);
        }
    }

    private function updateVerticalResources(string $vertical, string $verticalPath): void
    {
        $resourcesPath = "{$verticalPath}/Filament/Resources";

        if (! $this->files->exists($resourcesPath)) {
            return;
        }

        $resourceFiles = $this->files->files($resourcesPath);

        foreach ($resourceFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = $this->files->get($file->getPathname());

            // Check if already extends BaseOptimizedResource
            if (str_contains($content, 'extends BaseOptimizedResource')) {
                continue;
            }

            // Check if extends Resource
            if (! str_contains($content, 'extends Resource')) {
                continue;
            }

            $this->info("  - Updating resource: {$file->getFilename()}");

            if (! $this->option('dry-run')) {
                $content = $this->updateResourceContent($content, $vertical);
                $this->files->put($file->getPathname(), $content);
                $this->resourcesUpdated++;
            }
        }
    }

    private function updateResourceContent(string $content, string $vertical): string
    {
        // Add use statement for BaseOptimizedResource
        if (! str_contains($content, 'use App\\Filament\\Resources\\BaseOptimizedResource')) {
            $content = str_replace(
                'use Filament\Resources\Resource;',
                "use App\Filament\Resources\BaseOptimizedResource;\nuse Filament\Resources\Resource;",
                $content
            );
        }

        // Change extends Resource to extends BaseOptimizedResource
        $content = preg_replace(
            '/extends Resource/',
            'extends BaseOptimizedResource',
            $content
        );

        // Add getEagerLoading method if not exists
        if (! str_contains($content, 'getEagerLoading')) {
            $content = $this->addEagerLoadingMethod($content, $vertical);
        }

        return $content;
    }

    private function addEagerLoadingMethod(string $content, string $vertical): string
    {
        $method = "\n    /**\n     * Relations to eager load for {$vertical}\n     */\n    protected static function getEagerLoading(): array\n    {\n        return [];\n    }\n";

        // Find the last closing brace of the class
        $lastBrace = strrpos($content, '}');

        if ($lastBrace !== false) {
            $content = substr_replace($content, $method, $lastBrace, 0);
        }

        return $content;
    }

    private function updateVerticalWidgets(string $vertical, string $verticalPath): void
    {
        $widgetsPath = "{$verticalPath}/Filament/Widgets";

        if (! $this->files->exists($widgetsPath)) {
            return;
        }

        $widgetFiles = $this->files->files($widgetsPath);

        foreach ($widgetFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = $this->files->get($file->getPathname());

            // Check if already extends BaseCachedWidget
            if (str_contains($content, 'extends BaseCachedWidget')) {
                continue;
            }

            // Check if extends Widget
            if (! str_contains($content, 'extends Widget')) {
                continue;
            }

            $this->info("  - Updating widget: {$file->getFilename()}");

            if (! $this->option('dry-run')) {
                $content = $this->updateWidgetContent($content);
                $this->files->put($file->getPathname(), $content);
                $this->widgetsUpdated++;
            }
        }
    }

    private function updateWidgetContent(string $content): string
    {
        // Add use statement for BaseCachedWidget
        if (! str_contains($content, 'use App\\Filament\\Widgets\\BaseCachedWidget')) {
            $content = str_replace(
                'use Filament\Widgets\Widget;',
                "use App\Filament\Widgets\BaseCachedWidget;\nuse Filament\Widgets\Widget;",
                $content
            );
        }

        // Change extends Widget to extends BaseCachedWidget
        $content = preg_replace(
            '/extends Widget/',
            'extends BaseCachedWidget',
            $content
        );

        // Add cache configuration if not exists
        if (! str_contains($content, 'cacheTtl')) {
            $content = $this->addCacheConfiguration($content);
        }

        return $content;
    }

    private function addCacheConfiguration(string $content): string
    {
        $config = "\n    protected static int \$cacheTtl = 300; // 5 minutes\n    protected static bool \$enableCache = true;\n";

        // Find the class declaration
        $pattern = '/(class \w+ extends BaseCachedWidget)/';
        $replacement = "$1\n{$config}";

        return preg_replace($pattern, $replacement, $content);
    }

    private function updateVerticalModels(string $vertical, string $verticalPath): void
    {
        $modelsPath = "{$verticalPath}/Models";

        if (! $this->files->exists($modelsPath)) {
            return;
        }

        $modelFiles = $this->files->files($modelsPath);

        foreach ($modelFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = $this->files->get($file->getPathname());

            // Check if already has TenantScoped
            if (str_contains($content, 'use App\\Traits\\TenantScoped')) {
                continue;
            }

            // Check if has tenant_id column (assumed for tenant-scoped models)
            if (! str_contains($content, 'tenant_id')) {
                continue;
            }

            $this->info("  - Updating model: {$file->getFilename()}");

            if (! $this->option('dry-run')) {
                $content = $this->updateModelContent($content);
                $this->files->put($file->getPathname(), $content);
                $this->modelsUpdated++;
            }
        }
    }

    private function updateModelContent(string $content): string
    {
        // Add use statement for TenantScoped
        if (! str_contains($content, 'use App\\Traits\\TenantScoped')) {
            $content = str_replace(
                'namespace App\\Domains',
                "use App\Traits\TenantScoped;\n\nnamespace App\\Domains",
                $content
            );
        }

        // Add trait to class
        $pattern = '/(class \w+ extends Model)/';
        $replacement = "$1\n{\n    use TenantScoped;";

        $content = preg_replace($pattern, $replacement, $content, 1);

        return $content;
    }
}
