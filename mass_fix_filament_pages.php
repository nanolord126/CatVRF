<?php

declare(strict_types=1);

/**
 * Mass Fix Filament Pages Script
 * 
 * Automatically fixes all empty/minimal Filament Pages
 * Applies proper patterns for CreateRecord, EditRecord, ViewRecord, ListRecords
 */

$basePath = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';
$resourcePath = realpath($basePath);
$fixed = 0;
$errors = 0;

if (!is_dir($resourcePath)) {
    echo "❌ Error: Path not found: $basePath\n";
    exit(1);
}

// Get all Pages
$pageFinder = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($resourcePath, \RecursiveDirectoryIterator::SKIP_DOTS)
);

$pages = [];
foreach ($pageFinder as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getPath(), '/Pages') !== false) {
        $pages[] = (string)$file;
    }
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FILAMENT PAGES MASS FIX SCRIPT                               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
echo "Found " . count($pages) . " pages to analyze\n\n";

foreach ($pages as $pagePath) {
    $content = file_get_contents($pagePath);
    $relativePath = str_replace($resourcePath, '', $pagePath);
    $lines = count(explode("\n", $content));
    
    // Check if page needs fixing
    $needsFix = false;
    
    // Too short = probably empty
    if ($lines < 20) {
        $needsFix = true;
    }
    
    // Missing implementations in CreateRecord/EditRecord
    if ((strpos($content, 'CreateRecord') !== false || strpos($content, 'EditRecord') !== false) &&
        strpos($content, 'handleRecord') === false && 
        strpos($content, 'boot(') === false &&
        strpos($content, '__construct(') === false) {
        $needsFix = true;
    }
    
    // Missing authorizeAccess in View/List/Show
    if ((strpos($content, 'ViewRecord') !== false || 
         strpos($content, 'ShowRecord') !== false || 
         strpos($content, 'ListRecords') !== false) &&
        strpos($content, 'authorizeAccess') === false) {
        $needsFix = true;
    }
    
    if (!$needsFix) {
        continue;
    }
    
    // Determine page type
    $classMatch = [];
    preg_match('/class (\w+) extends (\w+)/', $content, $classMatch);
    if (empty($classMatch)) {
        continue;
    }
    
    $className = $classMatch[1];
    $parentClass = $classMatch[2];
    
    // Extract namespace
    $namespaceMatch = [];
    preg_match('/namespace (.+?);/', $content, $namespaceMatch);
    $namespace = $namespaceMatch[1] ?? null;
    
    // Extract resource class
    $resourceMatch = [];
    preg_match('/\$resource = (\S+)::class/', $content, $resourceMatch);
    $resourceClass = $resourceMatch[1] ?? null;
    
    if (!$namespace || !$resourceClass) {
        $errors++;
        continue;
    }
    
    // Generate proper implementation
    $newContent = null;
    
    if (strpos($parentClass, 'CreateRecord') !== false) {
        $newContent = generateCreateRecord($namespace, $className, $resourceClass);
    } elseif (strpos($parentClass, 'EditRecord') !== false) {
        $newContent = generateEditRecord($namespace, $className, $resourceClass);
    } elseif (strpos($parentClass, 'ViewRecord') !== false || strpos($parentClass, 'ShowRecord') !== false) {
        $newContent = generateViewRecord($namespace, $className, $resourceClass);
    } elseif (strpos($parentClass, 'ListRecords') !== false) {
        $newContent = generateListRecords($namespace, $className, $resourceClass);
    }
    
    if ($newContent) {
        file_put_contents($pagePath, $newContent);
        $fixed++;
        echo "✅ Fixed: $relativePath\n";
    } else {
        $errors++;
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ Fixed:  $fixed pages\n";
echo "❌ Errors: $errors pages\n";
echo "═══════════════════════════════════════════════════════════════\n";

function generateCreateRecord($namespace, $className, $resourceClass): string
{
    return <<<PHP
<?php

declare(strict_types=1);

namespace $namespace;

use $resourceClass;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Throwable;

final class $className extends CreateRecord
{
    protected static string \$resource = {$resourceClass}::class;

    protected Guard \$guard;
    protected LogManager \$log;
    protected DatabaseManager \$db;
    protected Request \$request;
    protected Gate \$gate;
    protected RateLimiter \$rateLimiter;

    public function boot(
        Guard \$guard,
        LogManager \$log,
        DatabaseManager \$db,
        Request \$request,
        Gate \$gate,
        RateLimiter \$rateLimiter
    ): void {
        \$this->guard = \$guard;
        \$this->log = \$log;
        \$this->db = \$db;
        \$this->request = \$request;
        \$this->gate = \$gate;
        \$this->rateLimiter = \$rateLimiter;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
    }

    protected function handleRecordCreation(array \$data): Model
    {
        \$user = \$this->guard->user();
        \$key = 'create-record:' . (\$user?->id ?? \$this->request->ip());

        if (\$this->rateLimiter->tooManyAttempts(\$key, 20)) {
            Notification::make()
                ->title(__('Слишком много запросов'))
                ->danger()
                ->send();
            \$this->halt();
        }

        \$this->rateLimiter->hit(\$key, 3600);

        try {
            return \$this->db->transaction(function () use (\$data, \$user) {
                \$filtered = array_filter(\$data, static fn(\$value) => \$value !== null);
                \$record = parent::handleRecordCreation(\$filtered);

                if (\$record && \$user) {
                    \$correlationId = \$this->request->header('X-Correlation-ID') ?? (string) Str::uuid();
                    \$this->log->channel('audit')->info('Record created', [
                        'id' => \$record->id,
                        'user_id' => \$user->id,
                        'tenant_id' => filament()->getTenant()?->id,
                        'ip' => \$this->request->ip(),
                        'correlation_id' => \$correlationId,
                    ]);
                }

                Notification::make()->success()->title(__('Создано'))->send();
                return \$record;
            });
        } catch (Throwable \$e) {
            \$user = \$this->guard->user();
            \$this->log->channel('audit')->error('Creation failed', [
                'error' => \$e->getMessage(),
                'user_id' => \$user?->id,
            ]);
            Notification::make()->danger()->title(__('Ошибка'))->send();
            throw \$e;
        }
    }

    public function getTitle(): string
    {
        return __('Создать');
    }
}
PHP;
}

function generateEditRecord($namespace, $className, $resourceClass): string
{
    return <<<PHP
<?php

declare(strict_types=1);

namespace $namespace;

use $resourceClass;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Throwable;

final class $className extends EditRecord
{
    protected static string \$resource = {$resourceClass}::class;

    protected Guard \$guard;
    protected LogManager \$log;
    protected DatabaseManager \$db;
    protected Request \$request;
    protected Gate \$gate;

    public function boot(
        Guard \$guard,
        LogManager \$log,
        DatabaseManager \$db,
        Request \$request,
        Gate \$gate
    ): void {
        \$this->guard = \$guard;
        \$this->log = \$log;
        \$this->db = \$db;
        \$this->request = \$request;
        \$this->gate = \$gate;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
    }

    protected function handleRecordUpdate(Model \$record, array \$data): Model
    {
        try {
            return \$this->db->transaction(function () use (\$record, \$data) {
                \$user = \$this->guard->user();
                \$filtered = array_filter(\$data, static fn(\$value) => \$value !== null);
                \$record = parent::handleRecordUpdate(\$record, \$filtered);

                if (\$user) {
                    \$correlationId = \$this->request->header('X-Correlation-ID') ?? (string) Str::uuid();
                    \$this->log->channel('audit')->info('Record updated', [
                        'id' => \$record->id,
                        'user_id' => \$user->id,
                        'tenant_id' => filament()->getTenant()?->id,
                        'correlation_id' => \$correlationId,
                    ]);
                }

                Notification::make()->success()->title(__('Обновлено'))->send();
                return \$record;
            });
        } catch (Throwable \$e) {
            \$this->log->channel('audit')->error('Update failed', [
                'id' => \$record->id,
                'error' => \$e->getMessage(),
            ]);
            Notification::make()->danger()->title(__('Ошибка'))->send();
            throw \$e;
        }
    }

    public function getTitle(): string
    {
        return __('Редактировать');
    }
}
PHP;
}

function generateViewRecord($namespace, $className, $resourceClass): string
{
    return <<<PHP
<?php

declare(strict_types=1);

namespace $namespace;

use $resourceClass;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;

final class $className extends ViewRecord
{
    protected static string \$resource = {$resourceClass}::class;

    protected Guard \$guard;
    protected LogManager \$log;
    protected Request \$request;
    protected Gate \$gate;

    public function boot(
        Guard \$guard,
        LogManager \$log,
        Request \$request,
        Gate \$gate
    ): void {
        \$this->guard = \$guard;
        \$this->log = \$log;
        \$this->request = \$request;
        \$this->gate = \$gate;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        \$user = \$this->guard->user();
        \$this->log->channel('audit')->info('Record viewed', [
            'id' => \$this->record?->id,
            'user_id' => \$user?->id,
            'tenant_id' => filament()->getTenant()?->id,
            'ip' => \$this->request->ip(),
        ]);
    }

    public function getTitle(): string
    {
        return \$this->record?->name ?? \$this->record?->title ?? __('Просмотр');
    }
}
PHP;
}

function generateListRecords($namespace, $className, $resourceClass): string
{
    return <<<PHP
<?php

declare(strict_types=1);

namespace $namespace;

use $resourceClass;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;

final class $className extends ListRecords
{
    protected static string \$resource = {$resourceClass}::class;

    protected Guard \$guard;
    protected LogManager \$log;
    protected Request \$request;
    protected Gate \$gate;

    public function boot(
        Guard \$guard,
        LogManager \$log,
        Request \$request,
        Gate \$gate
    ): void {
        \$this->guard = \$guard;
        \$this->log = \$log;
        \$this->request = \$request;
        \$this->gate = \$gate;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        \$user = \$this->guard->user();
        \$this->log->channel('audit')->info('List accessed', [
            'user_id' => \$user?->id,
            'tenant_id' => filament()->getTenant()?->id,
            'ip' => \$this->request->ip(),
        ]);
    }

    public function getTitle(): string
    {
        return __('Список');
    }
}
PHP;
}
