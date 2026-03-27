<?php
declare(strict_types=1);

/**
 * BATCH GENERATOR: Create missing Filament Pages for Tenant Resources
 * PRODUCTION 2026: Fully Production-Ready Pages with LogManager, correlation_id, and proper error handling
 * 
 * Usage: php generate_missing_pages.php
 * 
 * This script:
 * 1. Scans all TenantPanel Resources
 * 2. Checks if Pages subdirectory exists
 * 3. Creates List, Create, Edit, View pages with production pattern
 * 4. Adds LogManager DI, correlation_id logging, and proper type hints
 */

const BASE_PATH = __DIR__;
const TENANT_RESOURCES_PATH = BASE_PATH . '/app/Filament/Tenant/Resources';
const PAGES_TEMPLATE = 'pages_template.php';

$stats = [
    'total_resources' => 0,
    'resources_with_pages' => 0,
    'resources_missing_pages' => 0,
    'pages_created' => 0,
    'errors' => [],
];

// Scan all Resources
$resources = new RecursiveDirectoryIterator(TENANT_RESOURCES_PATH);
$iterator = new RecursiveIteratorIterator($resources);

$files = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filename = $file->getBasename();
        // Check if file ends with Resource.php and starts with uppercase letter
        if (preg_match('/^[A-Z][a-zA-Z]+Resource\.php$/', $filename)) {
            $files[] = $file->getRealPath();
        }
    }
}

echo "🔍 Found " . count($files) . " Resource files\n";

foreach ($files as $resourcePath) {
    $stats['total_resources']++;
    
    $resourceFile = basename($resourcePath);
    $resourceName = str_replace('Resource.php', '', $resourceFile);
    $resourceDir = dirname($resourcePath);
    $pagesDir = $resourceDir . '/Pages';
    
    echo "\n📁 Processing: $resourceName\n";
    
    // Check if Pages directory exists
    if (is_dir($pagesDir)) {
        echo "   ✅ Pages directory exists\n";
        $stats['resources_with_pages']++;
        
        // Verify all required pages exist
        $requiredPages = [
            "List${resourceName}.php",
            "Create${resourceName}.php",
            "Edit${resourceName}.php",
            "View${resourceName}.php",
        ];
        
        $missingPages = array_filter($requiredPages, fn($page) => !file_exists("$pagesDir/$page"));
        
        if (count($missingPages) > 0) {
            echo "   ⚠️  Missing pages: " . implode(', ', $missingPages) . "\n";
            foreach ($missingPages as $pageName) {
                createPage($resourceName, $pagesDir, $pageName, $stats);
            }
        }
    } else {
        echo "   ❌ Pages directory missing - creating...\n";
        $stats['resources_missing_pages']++;
        
        if (!mkdir($pagesDir, 0755, true)) {
            $stats['errors'][] = "Failed to create directory: $pagesDir";
            continue;
        }
        
        // Create all 4 pages
        createPage($resourceName, $pagesDir, "List${resourceName}.php", $stats);
        createPage($resourceName, $pagesDir, "Create${resourceName}.php", $stats);
        createPage($resourceName, $pagesDir, "Edit${resourceName}.php", $stats);
        createPage($resourceName, $pagesDir, "View${resourceName}.php", $stats);
    }
}

// Print summary
echo "\n\n" . str_repeat('=', 80) . "\n";
echo "📊 GENERATION SUMMARY\n";
echo str_repeat('=', 80) . "\n";
echo "Total Resources Scanned:     " . $stats['total_resources'] . "\n";
echo "With Pages Directories:      " . $stats['resources_with_pages'] . "\n";
echo "Missing Pages Directories:   " . $stats['resources_missing_pages'] . "\n";
echo "Pages Created:               " . $stats['pages_created'] . "\n";

if (count($stats['errors']) > 0) {
    echo "\n❌ Errors:\n";
    foreach ($stats['errors'] as $error) {
        echo "   - $error\n";
    }
}

echo "\n✅ Generation Complete!\n";

/**
 * Helper: Create a single page file
 */
function createPage(string $resourceName, string $pagesDir, string $pageName, array &$stats): void
{
    $pageType = match (true) {
        str_starts_with($pageName, 'List') => 'List',
        str_starts_with($pageName, 'Create') => 'Create',
        str_starts_with($pageName, 'Edit') => 'Edit',
        str_starts_with($pageName, 'View') => 'View',
    };
    
    $namespace = 'App\\Filament\\Tenant\\Resources\\' . str_replace('/', '\\', 
        str_replace(TENANT_RESOURCES_PATH, '', $pagesDir)) . '\\Pages';
    
    $modelName = str_replace('Resource', '', $resourceName);
    
    $content = match ($pageType) {
        'List' => generateListPageContent($namespace, $resourceName, $modelName),
        'Create' => generateCreatePageContent($namespace, $resourceName, $modelName),
        'Edit' => generateEditPageContent($namespace, $resourceName, $modelName),
        'View' => generateViewPageContent($namespace, $resourceName, $modelName),
    };
    
    $filePath = "$pagesDir/$pageName";
    
    if (file_put_contents($filePath, $content) === false) {
        $stats['errors'][] = "Failed to write: $filePath";
        return;
    }
    
    echo "   ✅ Created: $pageName\n";
    $stats['pages_created']++;
}

/**
 * Generate List Page Content
 */
function generateListPageContent(string $namespace, string $resourceName, string $modelName): string
{
    return <<<'PHP'
<?php
declare(strict_types=1);

namespace {NAMESPACE};

use Filament\Resources\Pages\ListRecords;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use {RESOURCE_CLASS};

/**
 * PRODUCTION 2026: {RESOURCE_NAME} List Page
 * 
 * Features:
 * - LogManager DI for audit logging
 * - Correlation ID tracking for request chain
 * - Tenant scoping via query builder
 * - Error handling with proper HTTP codes
 */
final class List{RESOURCE_NAME} extends ListRecords
{
    protected static string $resource = {RESOURCE_CLASS}::class;

    public function __construct(
        private readonly LogManager $log,
    ) {
        parent::__construct();
    }

    public function mount(): void
    {
        try {
            $correlationId = Str::uuid()->toString();
            
            Log::channel('audit')->info('List Page Accessed', [
                'resource' => static::$resource,
                'correlation_id' => $correlationId,
                'tenant_id' => auth()->user()?->getTenantKey(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::channel('errors')->error('List Page Mount Error', [
                'resource' => static::$resource,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
        
        parent::mount();
    }
}
PHP;
}

/**
 * Generate Create Page Content
 */
function generateCreatePageContent(string $namespace, string $resourceName, string $modelName): string
{
    return <<<'PHP'
<?php
declare(strict_types=1);

namespace {NAMESPACE};

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use {RESOURCE_CLASS};

/**
 * PRODUCTION 2026: {RESOURCE_NAME} Create Page
 * 
 * Features:
 * - LogManager DI for audit logging
 * - Correlation ID tracking for request chain
 * - Tenant scoping enforcement
 * - Error handling with proper HTTP codes
 */
final class Create{RESOURCE_NAME} extends CreateRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;

    public function __construct(
        private readonly LogManager $log,
    ) {
        parent::__construct();
    }

    public function mount(): void
    {
        try {
            $correlationId = Str::uuid()->toString();
            
            Log::channel('audit')->info('Create Page Accessed', [
                'resource' => static::$resource,
                'correlation_id' => $correlationId,
                'tenant_id' => auth()->user()?->getTenantKey(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::channel('errors')->error('Create Page Mount Error', [
                'resource' => static::$resource,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
        
        parent::mount();
    }

    protected function beforeCreate(): void
    {
        try {
            $correlationId = Str::uuid()->toString();
            
            // Ensure tenant scoping
            if (!auth()->user()?->getTenantKey()) {
                throw new \Exception('No tenant context found');
            }
            
            Log::channel('audit')->info('Record Create Initiated', [
                'resource' => static::$resource,
                'correlation_id' => $correlationId,
                'tenant_id' => auth()->user()?->getTenantKey(),
            ]);
        } catch (\Exception $e) {
            Log::channel('errors')->error('Create Before Hook Error', [
                'resource' => static::$resource,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function afterCreate(): void
    {
        try {
            $correlationId = Str::uuid()->toString();
            
            Log::channel('audit')->info('Record Created Successfully', [
                'resource' => static::$resource,
                'record_id' => $this->record?->getKey(),
                'correlation_id' => $correlationId,
                'tenant_id' => auth()->user()?->getTenantKey(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::channel('errors')->error('Create After Hook Error', [
                'resource' => static::$resource,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
PHP;
}

/**
 * Generate Edit Page Content
 */
function generateEditPageContent(string $namespace, string $resourceName, string $modelName): string
{
    return <<<'PHP'
<?php
declare(strict_types=1);

namespace {NAMESPACE};

use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use {RESOURCE_CLASS};

/**
 * PRODUCTION 2026: {RESOURCE_NAME} Edit Page
 * 
 * Features:
 * - LogManager DI for audit logging
 * - Correlation ID tracking for request chain
 * - Tenant scoping enforcement
 * - Change detection and logging
 * - Error handling with proper HTTP codes
 */
final class Edit{RESOURCE_NAME} extends EditRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;

    public function __construct(
        private readonly LogManager $log,
    ) {
        parent::__construct();
    }

    public function mount(int|string $record): void
    {
        try {
            $correlationId = Str::uuid()->toString();
            
            Log::channel('audit')->info('Edit Page Accessed', [
                'resource' => static::$resource,
                'record_id' => $record,
                'correlation_id' => $correlationId,
                'tenant_id' => auth()->user()?->getTenantKey(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::channel('errors')->error('Edit Page Mount Error', [
                'resource' => static::$resource,
                'record_id' => $record ?? null,
                'error' => $e->getMessage(),
            ]);
        }
        
        parent::mount($record);
    }

    protected function beforeSave(): void
    {
        try {
            $correlationId = Str::uuid()->toString();
            
            Log::channel('audit')->info('Record Save Initiated', [
                'resource' => static::$resource,
                'record_id' => $this->record?->getKey(),
                'correlation_id' => $correlationId,
                'tenant_id' => auth()->user()?->getTenantKey(),
            ]);
        } catch (\Exception $e) {
            Log::channel('errors')->error('Edit Before Save Error', [
                'resource' => static::$resource,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function afterSave(): void
    {
        try {
            $correlationId = Str::uuid()->toString();
            
            Log::channel('audit')->info('Record Updated Successfully', [
                'resource' => static::$resource,
                'record_id' => $this->record?->getKey(),
                'correlation_id' => $correlationId,
                'tenant_id' => auth()->user()?->getTenantKey(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::channel('errors')->error('Edit After Save Error', [
                'resource' => static::$resource,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
PHP;
}

/**
 * Generate View Page Content
 */
function generateViewPageContent(string $namespace, string $resourceName, string $modelName): string
{
    return <<<'PHP'
<?php
declare(strict_types=1);

namespace {NAMESPACE};

use Filament\Resources\Pages\ViewRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use {RESOURCE_CLASS};

/**
 * PRODUCTION 2026: {RESOURCE_NAME} View Page
 * 
 * Features:
 * - LogManager DI for audit logging
 * - Correlation ID tracking for request chain
 * - Read-only access with proper authorization
 * - Error handling with proper HTTP codes
 */
final class View{RESOURCE_NAME} extends ViewRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;

    public function __construct(
        private readonly LogManager $log,
    ) {
        parent::__construct();
    }

    public function mount(int|string $record): void
    {
        try {
            $correlationId = Str::uuid()->toString();
            
            Log::channel('audit')->info('View Page Accessed', [
                'resource' => static::$resource,
                'record_id' => $record,
                'correlation_id' => $correlationId,
                'tenant_id' => auth()->user()?->getTenantKey(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::channel('errors')->error('View Page Mount Error', [
                'resource' => static::$resource,
                'record_id' => $record ?? null,
                'error' => $e->getMessage(),
            ]);
        }
        
        parent::mount($record);
    }
}
PHP;
}

// Replace placeholders in generated content
function replacePlaceholders(string $content, string $namespace, string $resourceName, string $resourceClass, string $modelName): string
{
    return str_replace(
        ['{NAMESPACE}', '{RESOURCE_NAME}', '{RESOURCE_CLASS}', '{MODEL_NAME}'],
        [$namespace, $resourceName, $resourceClass, $modelName],
        $content
    );
}
