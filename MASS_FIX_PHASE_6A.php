<?php

declare(strict_types=1);

/**
 * PHASE 6A AUTOMATION: Mass-fix all remaining Filament Pages
 * 
 * Fixes all empty/minimal pages by applying consistent pattern:
 * - boot() method with DI (Guard, LogManager, DatabaseManager, Request, Gate)
 * - authorizeAccess() override with permission + tenant checks
 * - Error handling in mutation methods (handleRecordCreation/Update)
 * - Audit logging with correlation_id
 * 
 * Files already fixed manually (skip):
 * - Restaurant (4 pages)
 * - Hotel (4 pages)
 * - SportEvent (4 pages)
 * - Concert (4 pages)
 * - Flower (4 pages)
 * Total: 20 files will be skipped
 * 
 * Usage: php MASS_FIX_PHASE_6A.php
 */

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';
$alreadyFixed = [
    'RestaurantResource', 'HotelResource', 'SportEventResource', 'ConcertResource', 'FlowerResource'
];

$fixed = [];
$errors = [];
$skipped = [];
$alreadyHasBootMethod = [];

if (!is_dir($baseDir)) {
    die("Directory not found: $baseDir\n");
}

// Get all page files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$pageFiles = [];
foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        if (str_contains($path, '/Pages/') && preg_match('/(Create|Edit|List|View)[A-Z]/', basename($path))) {
            $pageFiles[] = $path;
        }
    }
}

echo "🔍 Found " . count($pageFiles) . " total page files\n";
echo "⏭️  Skipping 20 already-fixed files (Restaurant, Hotel, SportEvent, Concert, Flower)\n\n";

foreach ($pageFiles as $filePath) {
    $content = file_get_contents($filePath);
    
    // Skip already fixed resources
    $shouldSkip = false;
    foreach ($alreadyFixed as $resource) {
        if (str_contains($filePath, $resource)) {
            $shouldSkip = true;
            break;
        }
    }
    
    if ($shouldSkip) {
        $skipped[] = $filePath;
        continue;
    }
    
    // Skip if already has boot() method
    if (str_contains($content, 'public function boot(')) {
        $alreadyHasBootMethod[] = $filePath;
        continue;
    }
    
    // Skip very large files (likely already complex)
    if (strlen($content) > 2000) {
        $skipped[] = $filePath;
        continue;
    }
    
    try {
        $newContent = fixPage($filePath, $content);
        
        if ($newContent !== $content) {
            // Ensure UTF-8 without BOM and CRLF
            $newContent = "<?php\r\n" . substr($newContent, 5);
            
            file_put_contents($filePath, $newContent);
            $fixed[] = basename($filePath);
            echo "✅ " . basename(dirname(dirname($filePath))) . "/" . basename($filePath) . "\n";
        }
    } catch (Exception $e) {
        $errors[basename($filePath)] = $e->getMessage();
    }
}

echo "\n" . str_repeat('═', 90) . "\n";
echo "📊 RESULTS:\n";
echo str_repeat('═', 90) . "\n";
echo "✅ Fixed pages: " . count($fixed) . "\n";
echo "⏭️  Already have boot() method: " . count($alreadyHasBootMethod) . "\n";
echo "⏭️  Skipped (already fixed or complex): " . count($skipped) . "\n";
echo "❌ Errors: " . count($errors) . "\n";

if ($errors) {
    echo "\n⚠️  ERROR DETAILS:\n";
    foreach ($errors as $file => $error) {
        echo "  - $file: $error\n";
    }
}

echo "\n" . str_repeat('═', 90) . "\n";
echo "✨ Phase 6a automation complete!\n";

function fixPage(string $filePath, string $content): string
{
    // Extract class name and namespace
    if (!preg_match('/class\s+(\w+)\s+extends\s+(\w+)/', $content, $matches)) {
        throw new Exception('Cannot parse class');
    }
    
    $className = $matches[1];
    $pageType = determinePageType($className);
    $resourceName = extractResourceName($filePath);
    $modelName = extractModelName($filePath);
    
    // Build imports that are needed
    $imports = buildImportsForPageType($pageType);
    
    // Add missing imports
    $content = addMissingImports($content, $imports);
    
    // Find the class body start
    $classPos = strpos($content, 'final class ' . $className);
    if ($classPos === false) {
        throw new Exception('Cannot find class definition');
    }
    
    $bodyStart = strpos($content, '{', $classPos) + 1;
    
    // Find where to insert new methods (after protected static $resource line)
    $resourceLine = strpos($content, 'protected static string $resource', $bodyStart);
    if ($resourceLine === false) {
        // Try to find getHeaderActions instead
        $getHeaderPos = strpos($content, 'protected function getHeaderActions', $bodyStart);
        if ($getHeaderPos === false) {
            $getHeaderPos = strpos($content, 'public function getHeaderActions', $bodyStart);
        }
        $insertPoint = $getHeaderPos;
    } else {
        $resourceEnd = strpos($content, ';', $resourceLine);
        $insertPoint = $resourceEnd + 1;
    }
    
    // Generate boot() and auth methods based on page type
    $methodsToAdd = generateMethodsForPageType($pageType, $resourceName, $modelName);
    
    // Insert the methods before getHeaderActions
    $getHeaderPos = strpos($content, 'protected function getHeaderActions', $bodyStart);
    if ($getHeaderPos === false) {
        $getHeaderPos = strpos($content, 'public function getHeaderActions', $bodyStart);
    }
    
    if ($getHeaderPos !== false) {
        $content = substr_replace($content, "\r\n" . $methodsToAdd . "\r\n\r\n\t", $getHeaderPos, 0);
    } else {
        // If no getHeaderActions, add before closing brace
        $lastBrace = strrpos($content, '}');
        $content = substr_replace($content, "\r\n" . $methodsToAdd . "\r\n", $lastBrace, 0);
    }
    
    return $content;
}

function determinePageType(string $className): string
{
    if (str_contains($className, 'Create')) return 'Create';
    if (str_contains($className, 'Edit')) return 'Edit';
    if (str_contains($className, 'List')) return 'List';
    if (str_contains($className, 'View') || str_contains($className, 'Show')) return 'View';
    return 'Create';
}

function extractResourceName(string $filePath): string
{
    if (preg_match('/([A-Z][a-zA-Z0-9]+)Resource/', $filePath, $m)) {
        return $m[1];
    }
    return 'Resource';
}

function extractModelName(string $filePath): string
{
    // Try to infer from resource name
    if (preg_match('/([A-Z][a-zA-Z0-9]+)Resource/', $filePath, $m)) {
        return $m[1];
    }
    return 'Model';
}

function buildImportsForPageType(string $pageType): array
{
    $base = [
        'Filament\Notifications\Notification',
        'Illuminate\Contracts\Auth\Guard',
        'Illuminate\Contracts\Auth\Access\Gate',
        'Illuminate\Http\Request',
        'Illuminate\Log\LogManager',
        'Throwable',
    ];
    
    if ($pageType === 'Create' || $pageType === 'Edit') {
        array_unshift($base,
            'Illuminate\Database\DatabaseManager',
            'Illuminate\Database\Eloquent\Model',
            'Illuminate\Support\Str'
        );
    }
    
    if ($pageType === 'List') {
        $base[] = 'Filament\Actions';
    }
    
    return $base;
}

function addMissingImports(string $content, array $imports): string
{
    foreach ($imports as $import) {
        $useStatement = "use $import;";
        if (!str_contains($content, $useStatement)) {
            // Find last use statement
            if (preg_match_all('/^use .+;$/m', $content, $matches)) {
                $lastUse = end($matches[0]);
                $lastUsePos = strpos($content, $lastUse);
                $insertPos = strpos($content, ';', $lastUsePos) + 1;
                $content = substr_replace($content, "\r\nuse $import;", $insertPos, 0);
            }
        }
    }
    return $content;
}

function generateMethodsForPageType(string $pageType, string $resourceName, string $modelName): string
{
    $tpl = file_get_contents(__DIR__ . '/PHASE_6_METHOD_TEMPLATES.php');
    
    // Fallback if template doesn't exist
    if (empty($tpl)) {
        return generateSimpleBoot($pageType);
    }
    
    return $tpl;
}

function generateSimpleBoot(string $pageType): string
{
    return <<<'PHP'
protected Guard $guard;
	protected LogManager $log;
	protected Request $request;
	protected Gate $gate;

	public function boot(Guard $guard, LogManager $log, Request $request, Gate $gate): void
	{
		$this->guard = $guard;
		$this->log = $log;
		$this->request = $request;
		$this->gate = $gate;
	}

	protected function authorizeAccess(): void
	{
		parent::authorizeAccess();

		if ($this->record && !$this->gate->allows('view', $this->record)) {
			abort(403, __('Unauthorized'));
		}
	}
PHP;
}
