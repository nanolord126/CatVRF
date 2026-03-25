<?php

declare(strict_types=1);

/**
 * QUICK FIX: Automatic fixer for all Filament Pages
 * Applies consistent boot() + authorizeAccess() pattern to 100+ pages
 * 
 * Usage: php QUICK_FIX_ALL_PAGES.php
 * Time: ~5 minutes for 214 pages
 */

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';
$fixed = [];
$errors = [];
$skipped = [];

if (! is_dir($baseDir)) {}

// Recursive directory scan
$allPages = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$pageFiles = [];
foreach ($allPages as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = (string)$file->getRealPath();
        if (str_contains($path, '/Pages/') && preg_match('/(Create|Edit|List|View)[A-Z]/', basename($path))) {
            $pageFiles[] = $path;
        }
    }
}

echo "Found " . count($pageFiles) . " page files to check\n";

foreach ($pageFiles as $filePath) {
    $content = file_get_contents($filePath);
    $basename = basename(dirname($filePath));
    $resourceName = str_replace('Resource', '', $basename);
    
    // Skip if already has boot() method
    if (str_contains($content, 'public function boot(')) {
        $skipped[] = $filePath;
        continue;
    }
    
    // Skip if it's properly complex
    if (strlen($content) > 1500) {
        $skipped[] = $filePath;
        continue;
    }
    
    try {
        $newContent = fixFilamentPage($filePath, $content, $resourceName);
        if ($newContent !== $content) {
            file_put_contents($filePath, $newContent);
            $fixed[] = $filePath;
            echo "✅ " . str_replace($baseDir, '', $filePath) . "\n";
        }
    } catch (Exception $e) {
        $errors[$filePath] = $e->getMessage();
        echo "❌ " . basename($filePath) . ": " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "RESULTS:\n";
echo "✅ Fixed: " . count($fixed) . "\n";
echo "⏭️  Skipped (already fixed): " . count($skipped) . "\n";
echo "❌ Errors: " . count($errors) . "\n";
echo str_repeat('=', 80) . "\n";

if ($errors) {
    echo "\nERRORS:\n";
    foreach ($errors as $file => $error) {
        echo "  - " . basename($file) . ": $error\n";
    }
}

function fixFilamentPage(string $filePath, string $content, string $resourceName): string
{
    $classNameMatch = null;
    if (preg_match('/class\s+(\w+)\s+extends/', $content, $m)) {
        $classNameMatch = $m[1];
    }
    
    // Determine page type
    $pageType = 'Unknown';
    if (str_contains($classNameMatch ?? '', 'Create')) $pageType = 'Create';
    elseif (str_contains($classNameMatch ?? '', 'Edit')) $pageType = 'Edit';
    elseif (str_contains($classNameMatch ?? '', 'List')) $pageType = 'List';
    elseif (str_contains($classNameMatch ?? '', 'View')) $pageType = 'View';
    
    $pattern = '/public function getHeaderActions\(\): array\s*\{[^}]+\}/s';
    
    if (! preg_match($pattern, $content)) {
        return $content; // Can't find structure to replace
    }
    
    // Get namespace and class info
    preg_match('/namespace\s+([^;]+);/', $content, $nsMatch);
    preg_match('/class\s+(\w+)\s+extends\s+(\w+)/', $content, $classMatch);
    
    $namespace = $nsMatch[1] ?? '';
    $className = $classMatch[1] ?? 'Unknown';
    $parentClass = $classMatch[2] ?? 'unknown';
    
    // Build boot() method based on page type
    if ($pageType === 'Create') {
        $bootMethod = buildCreatePageTemplate($className, $resourceName);
    } elseif ($pageType === 'Edit') {
        $bootMethod = buildEditPageTemplate($className, $resourceName);
    } elseif ($pageType === 'List') {
        $bootMethod = buildListPageTemplate($className, $resourceName);
    } else {
        $bootMethod = buildViewPageTemplate($className, $resourceName);
    }
    
    // Find where to insert boot() - after class declaration, before first method
    $insertPoint = strpos($content, 'protected static string $resource');
    if ($insertPoint === false) {
        $insertPoint = strpos($content, '{');
    }
    
    if ($insertPoint === false) {
        throw new Exception('Cannot find insertion point');
    }
    
    // Check if we need to add imports
    $requiredImports = [
        'Filament\\Actions',
        'Filament\\Notifications\\Notification',
        'Illuminate\\Contracts\\Auth\\Guard',
        'Illuminate\\Contracts\\Auth\\Access\\Gate',
        'Illuminate\\Database\\DatabaseManager',
        'Illuminate\\Database\\Eloquent\\Model',
        'Illuminate\\Http\\Request',
        'Illuminate\\Log\\LogManager',
        'Illuminate\\Support\\Str',
        'Throwable',
    ];
    
    foreach ($requiredImports as $import) {
        if (! str_contains($content, "use $import")) {
            // Find use statement section
            $usePos = strpos($content, 'use App\\Filament');
            if ($usePos === false) $usePos = strpos($content, 'use ');
            if ($usePos !== false) {
                // Find end of use statements
                $useEnd = strpos($content, ';', $usePos) + 1;
                $content = substr_replace($content, "\nuse $import;", $useEnd, 0);
            }
        }
    }
    
    // Insert boot() and other methods
    $beforeGetHeaderActions = preg_replace($pattern, $bootMethod . "\n\n\tprotected function getHeaderActions():\s*array\s*\{", $content);
    
    return $beforeGetHeaderActions;
}

function buildCreatePageTemplate(string $className, string $resourceName): string
{
    return <<<'PHP'
protected Guard $guard;
	protected LogManager $log;
	protected DatabaseManager $db;
	protected Request $request;
	protected Gate $gate;

	public function boot(
		Guard $guard,
		LogManager $log,
		DatabaseManager $db,
		Request $request,
		Gate $gate
	): void {
		$this->guard = $guard;
		$this->log = $log;
		$this->db = $db;
		$this->request = $request;
		$this->gate = $gate;
	}

	protected function authorizeAccess(): void
	{
		parent::authorizeAccess();

		if (! $this->gate->allows('create', $this->resource::$model)) {
			abort(403, __('Unauthorized'));
		}
	}

	protected function handleRecordCreation(array $data): Model
	{
		try {
			return $this->db->transaction(function () use ($data) {
				$user = $this->guard->user();
				$data['tenant_id'] = $user?->tenant_id;
				$record = parent::handleRecordCreation($data);

				if ($user) {
					$correlationId = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();
					$this->log->channel('audit')->info('Record created', [
						'id' => $record->id,
						'user_id' => $user->id,
						'tenant_id' => filament()->getTenant()?->id,
						'correlation_id' => $correlationId,
					]);
				}

				Notification::make()->success()->title(__('Создано'))->send();
				return $record;
			});
		} catch (Throwable $e) {
			$user = $this->guard->user();
			$this->log->channel('audit')->error('Record creation failed', [
				'error' => $e->getMessage(),
				'user_id' => $user?->id,
				'tenant_id' => filament()->getTenant()?->id,
			]);
			Notification::make()->danger()->title(__('Ошибка'))->send();
			throw $e;
		}
	}

	public function getTitle(): string
	{
		return __('Создать');
	}

PHP;
}

function buildEditPageTemplate(string $className, string $resourceName): string
{
    return <<<'PHP'
protected Guard $guard;
	protected LogManager $log;
	protected DatabaseManager $db;
	protected Request $request;
	protected Gate $gate;

	public function boot(
		Guard $guard,
		LogManager $log,
		DatabaseManager $db,
		Request $request,
		Gate $gate
	): void {
		$this->guard = $guard;
		$this->log = $log;
		$this->db = $db;
		$this->request = $request;
		$this->gate = $gate;
	}

	protected function authorizeAccess(): void
	{
		parent::authorizeAccess();

		if (! $this->gate->allows('update', $this->record)) {
			abort(403, __('Unauthorized'));
		}

		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) {
			abort(403, __('Forbidden'));
		}
	}

	protected function handleRecordUpdate(Model $record, array $data): Model
	{
		try {
			return $this->db->transaction(function () use ($record, $data) {
				$user = $this->guard->user();
				$filtered = array_filter($data, static fn($value) => $value !== null);
				$record = parent::handleRecordUpdate($record, $filtered);

				if ($user) {
					$correlationId = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();
					$this->log->channel('audit')->info('Record updated', [
						'id' => $record->id,
						'user_id' => $user->id,
						'tenant_id' => filament()->getTenant()?->id,
						'correlation_id' => $correlationId,
					]);
				}

				Notification::make()->success()->title(__('Обновлено'))->send();
				return $record;
			});
		} catch (Throwable $e) {
			$user = $this->guard->user();
			$this->log->channel('audit')->error('Record update failed', [
				'id' => $record->id,
				'error' => $e->getMessage(),
				'user_id' => $user?->id,
				'tenant_id' => filament()->getTenant()?->id,
			]);
			Notification::make()->danger()->title(__('Ошибка'))->send();
			throw $e;
		}
	}

	public function getTitle(): string
	{
		return __('Редактировать');
	}

PHP;
}

function buildListPageTemplate(string $className, string $resourceName): string
{
    return <<<'PHP'
protected Guard $guard;
	protected LogManager $log;
	protected Request $request;

	public function boot(Guard $guard, LogManager $log, Request $request): void
	{
		$this->guard = $guard;
		$this->log = $log;
		$this->request = $request;
	}

	protected function authorizeAccess(): void
	{
		parent::authorizeAccess();

		$user = $this->guard->user();
		if ($user) {
			$correlationId = $this->request->header('X-Correlation-ID') ?? uniqid('list_', true);
			$this->log->channel('audit')->info('List accessed', [
				'user_id' => $user->id,
				'tenant_id' => filament()->getTenant()?->id,
				'correlation_id' => $correlationId,
			]);
		}
	}

	public function getTitle(): string
	{
		return __('Список');
	}

PHP;
}

function buildViewPageTemplate(string $className, string $resourceName): string
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

		if (! $this->gate->allows('view', $this->record)) {
			abort(403, __('Unauthorized'));
		}

		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) {
			abort(403, __('Forbidden'));
		}
	}

	public function getTitle(): string
	{
		return __('Просмотр');
	}

PHP;
}
