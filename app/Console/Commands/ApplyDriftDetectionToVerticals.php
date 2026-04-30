<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cache\CacheService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Support\Collection;

/**
 * Apply Feature Drift Detection to All Verticals
 *
 * This command automatically adds drift detection to AI Constructor Services
 * across all 64 business verticals in CatVRF.
 *
 * Usage:
 * php artisan drift:detect:apply-verticals [--dry-run] [--vertical=medical]
 */
final class ApplyDriftDetectionToVerticals extends Command
{
    public function __construct(
        private readonly FilesystemContract $files,
        private readonly CacheService $cacheService,
    ) {
        parent::__construct();
    }
    protected $signature = 'drift:detect:apply-verticals 
                            {--dry-run : Preview changes without applying}
                            {--vertical= : Apply to specific vertical only}';

    protected $description = 'Apply Feature Drift Detection to AI Constructor Services in all verticals';

    private int $filesModified = 0;

    private int $filesSkipped = 0;

    private int $filesErrored = 0;

    public function handle(): int
    {
        $this->info('Starting Feature Drift Detection application to verticals...');
        $this->newLine();

        $verticalPath = app_path('Domains');
        $targetVertical = $this->option('vertical');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No files will be modified');
            $this->newLine();
        }

        // Find all vertical directories
        $verticals = $this->findVerticalDirectories($verticalPath, $targetVertical);

        if ($verticals->isEmpty()) {
            $this->error('No vertical directories found');

            return Command::FAILURE;
        }

        $verticalCount = $verticals->count();
        $this->info("Found {$verticalCount} vertical(s) to process");
        $this->newLine();

        // Process each vertical
        foreach ($verticals as $vertical) {
            $this->processVertical($vertical, $isDryRun);
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Files modified: {$this->filesModified}");
        $this->info("Files skipped: {$this->filesSkipped}");
        $this->info("Files errored: {$this->filesErrored}");

        if ($isDryRun) {
            $this->warn('This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    private function findVerticalDirectories(string $basePath, ?string $targetVertical): Collection
    {
        $directories = new Collection($this->files->directories($basePath));

        if ($targetVertical) {
            return $directories->filter(fn ($dir) => basename($dir) === $targetVertical);
        }

        // Filter out technical directories
        $excludeDirs = ['AI', 'Audit', 'B2B', 'BigData', 'Bonuses', 'Cart', 'Commissions',
            'Common', 'Compliance', 'DemandForecast', 'FraudML', 'ML', 'Notifications',
            'Payout', 'PromoCampaigns', 'Realtime', 'Recommendation', 'Referral',
            'Search', 'Security', 'UserProfile', 'VerticalName', 'Webhooks'];

        return $directories->reject(fn ($dir) => in_array(basename($dir), $excludeDirs, true));
    }

    private function processVertical(string $verticalPath, bool $isDryRun): void
    {
        $verticalName = basename($verticalPath);
        $this->info("Processing vertical: {$verticalName}");

        // Find AI Constructor Services
        $aiServices = $this->findAIServices($verticalPath);

        if ($aiServices->isEmpty()) {
            $this->line('  - No AI services found');
            $this->filesSkipped++;

            return;
        }

        foreach ($aiServices as $servicePath) {
            $this->processAIService($servicePath, $verticalName, $isDryRun);
        }
    }

    private function findAIServices(string $verticalPath): Collection
    {
        $aiPath = $verticalPath.'/Services/AI';

        if (! $this->files->exists($aiPath)) {
            return new Collection();
        }

        return (new Collection($this->files->files($aiPath)))
            ->filter(fn (\SplFileInfo $file) => $file->getExtension() === 'php')
            ->map(fn (\SplFileInfo $file) => $file->getPathname());
    }

    private function processAIService(string $servicePath, string $verticalName, bool $isDryRun): void
    {
        $serviceName = basename($servicePath, '.php');
        $this->line("  - Processing: {$serviceName}");

        try {
            $content = $this->files->get($servicePath);

            // Check if already has drift detection
            if ($this->hasDriftDetection($content)) {
                $this->line('    Already has drift detection - skipping');
                $this->filesSkipped++;

                return;
            }

            // Apply modifications
            $modifiedContent = $this->applyDriftDetection($content, $verticalName, $serviceName);

            if ($isDryRun) {
                $this->line('    [DRY RUN] Would modify: '.$this->getChangeSummary($content, $modifiedContent));
            } else {
                $this->files->put($servicePath, $modifiedContent);
                $this->line('    Modified successfully');
                $this->filesModified++;
            }
        } catch (\Exception $e) {
            $this->error("    Error processing {$serviceName}: {$e->getMessage()}");
            $this->filesErrored++;
        }
    }

    private function hasDriftDetection(string $content): bool
    {
        return str_contains($content, 'HasFeatureDriftDetection') ||
               str_contains($content, 'AbstractAIConstructorService');
    }

    private function applyDriftDetection(string $content, string $verticalName, string $serviceName): string
    {
        $verticalCode = $this->getVerticalCode($verticalName);

        // Add use statements
        $useStatements = $this->generateUseStatements();
        $content = $this->insertUseStatements($content, $useStatements);

        // Add trait or extend abstract class
        $content = $this->addTraitOrExtendClass($content, $serviceName);

        // Add dependencies to constructor
        $content = $this->addConstructorDependencies($content);

        // Add initialization call
        $content = $this->addInitializationCall($content);

        // Add drift check examples in key methods
        $content = $this->addDriftChecks($content);

        return $content;
    }

    private function generateUseStatements(): string
    {
        return "\nuse App\Services\ML\FeatureDriftDetectorService;";
    }

    private function insertUseStatements(string $content, string $useStatements): string
    {
        // Find the namespace line and add use statements after it
        $namespacePattern = '/(namespace\s+[^\n;]+;\n)/';

        if (preg_match($namespacePattern, $content, $matches)) {
            return str_replace(
                $matches[0],
                $matches[0].$useStatements."\n",
                $content
            );
        }

        return $content;
    }

    private function addTraitOrExtendClass(string $content, string $serviceName): string
    {
        // Add trait to class
        $classPattern = '/(final\s+class\s+'.preg_quote($serviceName).'\s+)/';

        $traitDeclaration = "\n{\n    use \App\Services\ML\Traits\HasFeatureDriftDetection;\n";

        if (preg_match($classPattern, $content, $matches)) {
            return str_replace(
                $matches[0]."{\n",
                $matches[0].$traitDeclaration,
                $content
            );
        }

        return $content;
    }

    private function addConstructorDependencies(string $content): string
    {
        // Find constructor and add drift detection dependencies
        $constructorPattern = '/(public\s+function\s+__construct\([^)]*)\)/';

        $dependencies = ",\n        private readonly FeatureDriftDetectorService \$driftDetector";

        if (preg_match($constructorPattern, $content, $matches)) {
            return str_replace(
                $matches[0],
                $matches[1].$dependencies.')',
                $content
            );
        }

        return $content;
    }

    private function addInitializationCall(string $content): string
    {
        // Find constructor body and add initialization
        $constructorBodyPattern = '/(public\s+function\s+__construct\([^)]*\)\s*\{[^}]*\n)/';

        $initialization = "\n        \$this->verticalCode = '{$this->getVerticalCodeFromService()}';\n        \$this->driftDetector = \$driftDetector;\n        \$this->initializeDriftDetection();\n";

        // This is simplified - in production would need more sophisticated parsing
        return $content;
    }

    private function addDriftChecks(string $content): string
    {
        // Add drift check comments before AI service calls
        // This is a placeholder - in production would add actual drift checks
        return $content;
    }

    private function getVerticalCode(string $verticalName): string
    {
        // Convert vertical name to code (e.g., MedicalHealthcare -> medical)
        $code = strtolower($verticalName);
        $code = str_replace([' ', '_'], '', $code);

        return $code;
    }

    private function getVerticalCodeFromService(): string
    {
        // This would be determined from the service context
        return 'default';
    }

    private function getChangeSummary(string $original, string $modified): string
    {
        $originalLines = (new Collection(explode("\n", $original)))->count();
        $modifiedLines = (new Collection(explode("\n", $modified)))->count();

        return '+'.($modifiedLines - $originalLines).' lines';
    }
}
