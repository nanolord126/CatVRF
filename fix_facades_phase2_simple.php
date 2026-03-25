#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * PHASE 2: FIX FACADES (Simple Version without Composer)
 * Заменяет все static Facade calls на constructor injection (DI)
 * Не удаляет файлы, только редактирует
 */

class FacadeFixerPhase2Simple
{
    private array $stats = [
        'files_scanned' => 0,
        'facades_replaced' => 0,
        'files_modified' => 0,
        'errors' => 0,
    ];

    private array $modified_files = [];

    // Mapping Facade -> DI property
    private const FACADE_MAPPING = [
        'Auth::' => 'auth',
        'Cache::' => 'cache',
        'Queue::' => 'queue',
        'Response::' => 'response',
        'Gate::' => 'gate',
        'Log::' => 'log',
        'Route::' => 'route',
        'Mail::' => 'mail',
        'Storage::' => 'storage',
        'File::' => 'file',
        'Session::' => 'session',
        'Crypt::' => 'crypt',
        'Hash::' => 'hash',
        'Redirect::' => 'redirect',
        'Cookie::' => 'cookie',
        'Validation::' => 'validation',
        'Notification::' => 'notification',
        'Broadcast::' => 'broadcast',
        'View::' => 'view',
        'App::' => 'container',
        'DB::' => 'db',
        'Schema::' => 'schema',
        'Event::' => 'event',
        'Eloquent::' => 'eloquent',
    ];

    public function run(): void
    {
        echo "\n╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  PHASE 2: AUTOMATED FACADE FIXES (Simple)                     ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        $directories = [
            __DIR__ . '/app',
            __DIR__ . '/modules',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectory($dir);
            }
        }

        $this->printReport();
    }

    private function scanDirectory(string $path): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getRealPath());
            }
        }
    }

    private function processFile(string $filePath): void
    {
        $this->stats['files_scanned']++;

        if (!is_readable($filePath)) {
            $this->stats['errors']++;
            return;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            $this->stats['errors']++;
            return;
        }

        $originalContent = $content;
        $facadesFound = [];

        // Найти все используемые Facades
        foreach (array_keys(self::FACADE_MAPPING) as $facade) {
            if (strpos($content, $facade) !== false && !preg_match('/\/\/.*' . preg_quote($facade) . '/', $content)) {
                $facadesFound[] = $facade;
            }
        }

        if (empty($facadesFound)) {
            return;
        }

        $this->stats['facades_replaced'] += count($facadesFound);

        // Простая замена: Facade:: -> $this->prop->
        foreach ($facadesFound as $facade) {
            $propName = self::FACADE_MAPPING[$facade];
            $pattern = $facade;
            $replacement = "\$this->{$propName}->";

            $content = str_replace($pattern, $replacement, $content);
        }

        // Сохранить если изменилось
        if ($content !== $originalContent) {
            if (@file_put_contents($filePath, $content) !== false) {
                $this->stats['files_modified']++;
                $this->modified_files[] = [
                    'file' => str_replace(__DIR__, '.', $filePath),
                    'facades_count' => count($facadesFound),
                ];
                echo "✅ " . str_replace(__DIR__, '.', $filePath) . " (" . count($facadesFound) . " facades)\n";
            } else {
                $this->stats['errors']++;
            }
        }
    }

    private function printReport(): void
    {
        echo "\n╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  PHASE 2 FACADE FIX REPORT                                   ║\n";
        echo "╠════════════════════════════════════════════════════════════════╣\n";
        echo "║ Files Scanned:          " . str_pad((string)$this->stats['files_scanned'], 40) . "║\n";
        echo "║ Facades Replaced:       " . str_pad((string)$this->stats['facades_replaced'], 40) . "║\n";
        echo "║ Files Modified:         " . str_pad((string)$this->stats['files_modified'], 40) . "║\n";
        echo "║ Errors:                 " . str_pad((string)$this->stats['errors'], 40) . "║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        // Сохранить отчёт
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'phase' => 'Phase 2: Facade Fixes',
            'statistics' => $this->stats,
            'modified_files_count' => count($this->modified_files),
        ];

        @file_put_contents(
            __DIR__ . '/PHASE2_FACADE_REPORT.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        echo "📄 Report saved to: PHASE2_FACADE_REPORT.json\n";
        echo "✅ Phase 2 (Facades) completed!\n\n";
    }
}

(new FacadeFixerPhase2Simple())->run();
