#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * PHASE 2: FIX FACADES
 * Заменяет все static Facade calls на constructor injection (DI)
 * Не удаляет файлы, только редактирует
 * 
 * Целевые Facades:
 * - Auth:: → private readonly AuthService $auth
 * - Cache:: → private readonly CacheService $cache
 * - Queue:: → private readonly QueueService $queue
 * - Response:: → private readonly ResponseService $response
 * - Gate:: → private readonly GateService $gate
 * - Log:: → private readonly LogService $log
 * - DB:: → private readonly DatabaseService $db (но не для global DB::transaction!)
 */

use Symfony\Component\Finder\Finder;

require_once __DIR__ . '/vendor/autoload.php';

class FacadeFixerPhase2
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
    ];

    public function run(): void
    {
        echo "\n╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  PHASE 2: AUTOMATED FACADE FIXES                              ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        $finder = new Finder();
        $finder
            ->files()
            ->in([
                __DIR__ . '/app',
                __DIR__ . '/modules',
            ])
            ->name('*.php')
            ->notPath('*/vendor/*')
            ->notPath('*/node_modules/*');

        foreach ($finder as $file) {
            $this->processFile($file->getRealPath());
        }

        $this->printReport();
    }

    private function processFile(string $filePath): void
    {
        $this->stats['files_scanned']++;

        if (!is_readable($filePath)) {
            $this->stats['errors']++;
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->stats['errors']++;
            return;
        }

        $originalContent = $content;
        $facadesFound = [];

        // Найти все используемые Facades
        foreach (array_keys(self::FACADE_MAPPING) as $facade) {
            if (strpos($content, $facade) !== false) {
                $facadesFound[] = $facade;
                $this->stats['facades_replaced']++;
            }
        }

        if (empty($facadesFound)) {
            return;
        }

        // Исправить класс
        $content = $this->injectDependencies($content, $facadesFound, $filePath);
        $content = $this->replaceFacadeCalls($content, $facadesFound);

        // Сохранить если изменилось
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) !== false) {
                $this->stats['files_modified']++;
                $this->modified_files[] = [
                    'file' => str_replace(__DIR__, '.', $filePath),
                    'facades' => $facadesFound,
                ];
                echo "✅ " . str_replace(__DIR__, '.', $filePath) . "\n";
            } else {
                $this->stats['errors']++;
            }
        }
    }

    private function injectDependencies(string $content, array $facadesFound, string $filePath): string
    {
        // Проверить, есть ли уже конструктор
        if (preg_match('/public\s+function\s+__construct\s*\(/i', $content)) {
            // Конструктор есть, добавить параметры
            return $this->addToExistingConstructor($content, $facadesFound);
        } else {
            // Конструктора нет, создать новый
            return $this->addNewConstructor($content, $facadesFound);
        }
    }

    private function addToExistingConstructor(string $content, array $facadesFound): string
    {
        $injections = $this->generateInjections($facadesFound);

        // Найти конструктор и добавить параметры
        $pattern = '/public\s+function\s+__construct\s*\(\s*([^)]*?)\s*\)\s*\{/i';

        $content = preg_replace_callback($pattern, function ($matches) use ($injections) {
            $existingParams = trim($matches[1]);
            $newParams = $this->mergeConstructorParams($existingParams, $injections);
            return "public function __construct(\n        {$newParams}\n    ) {";
        }, $content);

        // Добавить свойства перед конструктором
        $propertiesCode = $this->generateProperties($facadesFound);

        // Найти позицию конструктора и вставить свойства перед ним
        $pattern = '/(\n\s+)(public\s+function\s+__construct)/i';
        $content = preg_replace($pattern, "\n{$propertiesCode}\n    \$2", $content);

        return $content;
    }

    private function addNewConstructor(string $content, array $facadesFound): string
    {
        $injections = $this->generateInjections($facadesFound);
        $properties = $this->generateProperties($facadesFound);
        $assignments = $this->generateAssignments($facadesFound);

        $constructor = "\n    {$properties}\n\n    public function __construct(\n        {$injections}\n    ) {\n{$assignments}    }\n";

        // Найти последний метод класса и вставить конструктор перед ним или после открытия класса
        if (preg_match('/class\s+\w+\s*(\{|extends|implements)/i', $content)) {
            // Вставить после открытия класса
            $content = preg_replace('/(class\s+\w+[^{]*\{)/i', "\$1\n{$constructor}", $content);
        }

        return $content;
    }

    private function generateInjections(array $facadesFound): string
    {
        $injections = [];

        foreach ($facadesFound as $facade) {
            $propName = self::FACADE_MAPPING[$facade] ?? strtolower(str_replace('::', '', $facade));
            $serviceClass = $this->getFacadeService($facade);

            if ($serviceClass) {
                $injections[] = "private readonly {$serviceClass} \${$propName}";
            }
        }

        return implode(",\n        ", $injections);
    }

    private function generateProperties(array $facadesFound): string
    {
        $properties = [];

        foreach ($facadesFound as $facade) {
            $propName = self::FACADE_MAPPING[$facade] ?? strtolower(str_replace('::', '', $facade));
            $serviceClass = $this->getFacadeService($facade);

            if ($serviceClass) {
                $properties[] = "private readonly {$serviceClass} \${$propName},";
            }
        }

        return implode("\n    ", $properties);
    }

    private function generateAssignments(array $facadesFound): string
    {
        $assignments = [];

        foreach ($facadesFound as $facade) {
            $propName = self::FACADE_MAPPING[$facade] ?? strtolower(str_replace('::', '', $facade));
            $assignments[] = "        \$this->{$propName} = \${$propName};";
        }

        return implode("\n", $assignments);
    }

    private function mergeConstructorParams(string $existing, string $new): string
    {
        if (empty($existing)) {
            return $new;
        }

        return "{$existing},\n        {$new}";
    }

    private function replaceFacadeCalls(string $content, array $facadesFound): string
    {
        foreach ($facadesFound as $facade) {
            $propName = self::FACADE_MAPPING[$facade] ?? strtolower(str_replace('::', '', $facade));
            $pattern = '/' . preg_quote($facade, '/') . '/';
            $replacement = "\$this->{$propName}->";

            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function getFacadeService(string $facade): ?string
    {
        $mapping = [
            'Auth::' => '\Illuminate\Auth\AuthManager',
            'Cache::' => '\Illuminate\Cache\CacheManager',
            'Queue::' => '\Illuminate\Queue\QueueManager',
            'Response::' => '\Illuminate\Routing\ResponseFactory',
            'Gate::' => '\Illuminate\Auth\Access\Gate',
            'Log::' => '\Psr\Log\LoggerInterface',
            'Route::' => '\Illuminate\Routing\Router',
            'Mail::' => '\Illuminate\Mail\Mailer',
            'Storage::' => '\Illuminate\Filesystem\FilesystemManager',
            'File::' => '\Illuminate\Filesystem\Filesystem',
            'Session::' => '\Illuminate\Session\SessionManager',
            'Crypt::' => '\Illuminate\Encryption\Encrypter',
            'Hash::' => '\Illuminate\Hashing\HashManager',
            'Redirect::' => '\Illuminate\Routing\Redirector',
            'Cookie::' => '\Illuminate\Cookie\CookieJar',
            'Validation::' => '\Illuminate\Validation\Factory',
            'Notification::' => '\Illuminate\Notifications\Dispatcher',
            'Broadcast::' => '\Illuminate\Broadcasting\BroadcastManager',
            'View::' => '\Illuminate\View\Factory',
            'App::' => '\Illuminate\Container\Container',
        ];

        return $mapping[$facade] ?? null;
    }

    private function printReport(): void
    {
        echo "\n╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  PHASE 2 FACADE FIX REPORT                                   ║\n";
        echo "╠════════════════════════════════════════════════════════════════╣\n";
        echo "║ Files Scanned:          " . str_pad((string)$this->stats['files_scanned'], 40) . "║\n";
        echo "║ Facades Found:          " . str_pad((string)$this->stats['facades_replaced'], 40) . "║\n";
        echo "║ Files Modified:         " . str_pad((string)$this->stats['files_modified'], 40) . "║\n";
        echo "║ Errors:                 " . str_pad((string)$this->stats['errors'], 40) . "║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        // Сохранить отчёт
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'phase' => 'Phase 2: Facade Fixes',
            'statistics' => $this->stats,
            'modified_files' => $this->modified_files,
        ];

        file_put_contents(
            __DIR__ . '/PHASE2_FACADE_REPORT.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        echo "📄 Report saved to: PHASE2_FACADE_REPORT.json\n\n";
    }
}

(new FacadeFixerPhase2())->run();
