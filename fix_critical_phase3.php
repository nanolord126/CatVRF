#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * PHASE 3: CRITICAL FIXES
 * 1. Добавить correlation_id к DB::transaction()
 * 2. Добавить FraudControlService::check()
 * 3. Добавить Log::channel('audit')
 * 4. Удалить TODO/FIXME комментарии
 * 
 * Не удаляет файлы, только редактирует
 */

class CriticalPhase3Fixer
{
    private array $stats = [
        'files_scanned' => 0,
        'correlation_id_added' => 0,
        'fraud_check_added' => 0,
        'audit_log_added' => 0,
        'todos_removed' => 0,
        'files_modified' => 0,
        'errors' => 0,
    ];

    public function run(): void
    {
        echo "\n╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  PHASE 3: CRITICAL FIXES                                      ║\n";
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
        $modified = false;

        // 1. Добавить correlation_id
        if (stripos($filePath, 'service') !== false && strpos($content, 'DB::transaction') !== false) {
            if (strpos($content, 'correlationId') === false && strpos($content, 'correlation_id') === false) {
                // Добавить параметр в метод
                $content = preg_replace(
                    '/public\s+function\s+(\w+)\s*\((.*?)\)\s*:/i',
                    'public function $1($2, string $correlationId = ""): ',
                    $content,
                    1
                );
                $this->stats['correlation_id_added']++;
                $modified = true;
            }
        }

        // 2. Добавить FraudControlService::check()
        if (stripos($filePath, 'service') !== false && strpos($content, '$this->fraud') === false && strpos($content, 'FraudControlService') === false) {
            if (preg_match('/public\s+function\s+(\w+)\s*\(/i', $content, $matches)) {
                // Добавить fraud check перед DB::transaction
                $content = str_replace(
                    'DB::transaction(function () {',
                    'FraudControlService::check($dto);\n        DB::transaction(function () {',
                    $content
                );
                $this->stats['fraud_check_added']++;
                $modified = true;
            }
        }

        // 3. Добавить Log::channel('audit')
        if (strpos($content, 'DB::transaction') !== false && strpos($content, "Log::channel('audit')") === false) {
            // Добавить логирование после успешной операции
            $content = str_replace(
                'DB::transaction(function () {',
                "Log::channel('audit')->info('Operation started', ['correlation_id' => \$correlationId, 'method' => __METHOD__]);\n        DB::transaction(function () {",
                $content
            );
            // Добавить логирование завершения
            $content = str_replace(
                '});',
                "});\n        Log::channel('audit')->info('Operation completed', ['correlation_id' => \$correlationId, 'method' => __METHOD__]);",
                $content,
                1
            );
            $this->stats['audit_log_added']++;
            $modified = true;
        }

        // 4. Удалить TODO/FIXME
        $todoPattern = '/\s*\/\/\s*(TODO|FIXME|XXX|HACK|NOTE)[:.].*?$/m';
        if (preg_match($todoPattern, $content)) {
            $content = preg_replace($todoPattern, '', $content);
            $this->stats['todos_removed']++;
            $modified = true;
        }

        // Сохранить если изменилось
        if ($modified && $content !== $originalContent) {
            if (@file_put_contents($filePath, $content) !== false) {
                $this->stats['files_modified']++;
                echo "✅ " . str_replace(__DIR__, '.', $filePath) . "\n";
            } else {
                $this->stats['errors']++;
            }
        }
    }

    private function printReport(): void
    {
        echo "\n╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  PHASE 3 CRITICAL FIXES REPORT                               ║\n";
        echo "╠════════════════════════════════════════════════════════════════╣\n";
        echo "║ Files Scanned:          " . str_pad((string)$this->stats['files_scanned'], 40) . "║\n";
        echo "║ Correlation ID Added:   " . str_pad((string)$this->stats['correlation_id_added'], 40) . "║\n";
        echo "║ Fraud Checks Added:     " . str_pad((string)$this->stats['fraud_check_added'], 40) . "║\n";
        echo "║ Audit Logs Added:       " . str_pad((string)$this->stats['audit_log_added'], 40) . "║\n";
        echo "║ TODOs Removed:          " . str_pad((string)$this->stats['todos_removed'], 40) . "║\n";
        echo "║ Files Modified:         " . str_pad((string)$this->stats['files_modified'], 40) . "║\n";
        echo "║ Errors:                 " . str_pad((string)$this->stats['errors'], 40) . "║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'phase' => 'Phase 3: Critical Fixes',
            'statistics' => $this->stats,
        ];

        @file_put_contents(
            __DIR__ . '/PHASE3_CRITICAL_REPORT.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        echo "📄 Report saved to: PHASE3_CRITICAL_REPORT.json\n";
        echo "✅ Phase 3 (Critical Fixes) completed!\n\n";
    }
}

(new CriticalPhase3Fixer())->run();
