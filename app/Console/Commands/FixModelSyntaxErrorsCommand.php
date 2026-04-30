<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Fix Model Syntax Errors Command
 *
 * Fixes syntax errors introduced by the mass fix command:
 * - Removes extra braces after trait usage
 */
final class FixModelSyntaxErrorsCommand extends Command
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }
    protected $signature = 'filament:fix-model-syntax
                            {--dry-run : Show changes without applying}';

    protected $description = 'Fix syntax errors in models after mass fix';

    private int $fixed = 0;

    public function handle(): int
    {
        $this->info('Fixing model syntax errors in all verticals...');

        $domainsPath = app_path('Domains');

        if (! $this->files->exists($domainsPath)) {
            $this->error('Domains directory not found');

            return Command::FAILURE;
        }

        $directories = $this->files->directories($domainsPath);

        foreach ($directories as $verticalPath) {
            $modelsPath = $verticalPath.'/Models';

            if ($this->files->exists($modelsPath)) {
                $this->fixDirectory($modelsPath);
            }
        }

        $this->info("Fixed {$this->fixed} files");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No changes were applied');
        }

        return Command::SUCCESS;
    }

    private function fixDirectory(string $directory): void
    {
        $files = $this->files->files($directory);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $this->fixFile($file->getPathname());
            }
        }
    }

    private function fixFile(string $path): void
    {
        $content = $this->files->get($path);
        $original = $content;

        // Fix: Remove extra brace after trait usage (multiline pattern)
        $content = preg_replace('/(use TenantScoped;)\s*\{\s*\n/', "$1\n", $content);
        $content = preg_replace('/(use TenantScoped;)\s*\{/', '$1', $content);

        // Also fix single-line versions
        $content = str_replace('use TenantScoped;{', 'use TenantScoped;', $content);

        if ($content !== $original) {
            $this->info('Fixing: '.basename($path));

            if (! $this->option('dry-run')) {
                $this->files->put($path, $content);
                $this->fixed++;
            }
        }
    }
}
