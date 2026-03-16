<?php

declare(strict_types=1);

/**
 * FINAL_COMPREHENSIVE_FIX
 * Fixes ALL pages that don't have proper boot(Guard method
 * Handles both completely empty pages and pages with only imports
 */

$baseDir = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\Marketplace';
$alreadyFixed = ['Restaurant', 'Hotel', 'SportEvent', 'Concert', 'Flower'];

$fixed = 0;
$skipped = 0;
$errors = [];

echo "🔧 Starting comprehensive fix for all pages without boot(Guard\n\n";

$resourceDirs = array_filter(
    array_map('trim', scandir($baseDir)),
    fn($d) => !in_array($d, ['.', '..', '.gitkeep']) && is_dir("$baseDir\\$d")
);

foreach ($resourceDirs as $resourceDir) {
    $resourcePath = "$baseDir\\$resourceDir";
    
    // Skip already fixed resources
    $skip = false;
    foreach ($alreadyFixed as $f) {
        if (str_contains($resourceDir, $f)) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;
    
    $pagesDir = "$resourcePath\\Pages";
    if (!is_dir($pagesDir)) continue;
    
    $files = glob("$pagesDir\\*.php");
    if (!$files) continue;
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // If it already has boot(Guard, skip
        if (strpos($content, 'public function boot(Guard') !== false) {
            $skipped++;
            continue;
        }
        
        // If file is huge (>3000 chars), it's probably already complex
        if (strlen($content) > 3000) {
            $skipped++;
            continue;
        }
        
        try {
            $fileName = basename($file);
            $type = getPageType($fileName);
            
            // Add all necessary imports first
            $content = addAllImports($content);
            
            // Find the class and resource line
            if (!preg_match('/^(final class \w+\s+extends\s+\w+)\s*\{/m', $content, $m)) {
                throw new Exception("No class found");
            }
            
            // Find resource property
            $resourcePos = strpos($content, 'protected static string $resource');
            if ($resourcePos === false) {
                throw new Exception("No resource property");
            }
            
            // Find end of resource property
            $resourceEnd = strpos($content, ';', $resourcePos) + 1;
            
            // Generate boot code
            $bootCode = generateBoot($type);
            
            // Insert boot method right after resource property
            $content = substr_replace($content, "\r\n\r\n\t" . $bootCode, $resourceEnd, 0);
            
            // Ensure getTitle exists
            if (strpos($content, 'public function getTitle()') === false) {
                $lastBrace = strrpos($content, '}');
                $title = getTitle($type);
                $content = substr_replace($content, "\r\n\r\n\t$title\r\n", $lastBrace, 0);
            }
            
            // Write file
            file_put_contents($file, $content);
            $fixed++;
            echo "✅ $resourceDir / $fileName\n";
            
        } catch (Exception $e) {
            $errors[basename($file)] = $e->getMessage();
            echo "❌ " . basename($file) . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n" . str_repeat('═', 80) . "\n";
echo "✨ FINAL COMPREHENSIVE FIX COMPLETE\n";
echo str_repeat('═', 80) . "\n";
echo "✅ Fixed: $fixed\n";
echo "⏭️  Skipped: $skipped\n";
echo "❌ Errors: " . count($errors) . "\n";

function getPageType(string $name): string
{
    if (str_contains($name, 'Create')) return 'Create';
    if (str_contains($name, 'Edit')) return 'Edit';
    if (str_contains($name, 'List')) return 'List';
    return 'View';
}

function addAllImports(string $content): string
{
    $needed = [
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
    
    foreach ($needed as $imp) {
        if (strpos($content, "use $imp;") === false) {
            // Find last use statement and add after
            preg_match_all('/(^use .+;$)/m', $content, $matches);
            if (!empty($matches[0])) {
                $lastUse = end($matches[0]);
                $pos = strpos($content, $lastUse) + strlen($lastUse);
                $content = substr_replace($content, "\r\nuse $imp;", $pos, 0);
            }
        }
    }
    
    return $content;
}

function generateBoot(string $type): string
{
    if ($type === 'Create') {
        return <<<'PHP'
protected Guard $guard;
	protected Gate $gate;
	protected LogManager $log;
	protected Request $request;
	protected DatabaseManager $db;

	public function boot(Guard $guard, Gate $gate, LogManager $log, Request $request, DatabaseManager $db): void
	{
		$this->guard = $guard;
		$this->gate = $gate;
		$this->log = $log;
		$this->request = $request;
		$this->db = $db;
	}

	protected function authorizeAccess(): void
	{
		parent::authorizeAccess();
		if (!$this->gate->allows('create', $this->resource::$model)) {
			abort(403);
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
					$this->log->channel('audit')->info('Created', ['id' => $record->id, 'user' => $user->id]);
				}
				Notification::make()->success()->send();
				return $record;
			});
		} catch (Throwable $e) {
			Notification::make()->danger()->send();
			throw $e;
		}
	}
PHP;
    }
    
    if ($type === 'Edit') {
        return <<<'PHP'
protected Guard $guard;
	protected Gate $gate;
	protected LogManager $log;
	protected Request $request;
	protected DatabaseManager $db;

	public function boot(Guard $guard, Gate $gate, LogManager $log, Request $request, DatabaseManager $db): void
	{
		$this->guard = $guard;
		$this->gate = $gate;
		$this->log = $log;
		$this->request = $request;
		$this->db = $db;
	}

	protected function authorizeAccess(): void
	{
		parent::authorizeAccess();
		if (!$this->gate->allows('update', $this->record)) abort(403);
		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) abort(403);
	}

	protected function handleRecordUpdate(Model $record, array $data): Model
	{
		try {
			return $this->db->transaction(function () use ($record, $data) {
				$record = parent::handleRecordUpdate($record, $data);
				if ($this->guard->user()) {
					$this->log->channel('audit')->info('Updated', ['id' => $record->id]);
				}
				Notification::make()->success()->send();
				return $record;
			});
		} catch (Throwable $e) {
			Notification::make()->danger()->send();
			throw $e;
		}
	}
PHP;
    }
    
    if ($type === 'List') {
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
			$this->log->channel('audit')->info('List accessed', ['user_id' => $user->id]);
		}
	}
PHP;
    }
    
    // View
    return <<<'PHP'
protected Guard $guard;
	protected Gate $gate;
	protected LogManager $log;
	protected Request $request;

	public function boot(Guard $guard, Gate $gate, LogManager $log, Request $request): void
	{
		$this->guard = $guard;
		$this->gate = $gate;
		$this->log = $log;
		$this->request = $request;
	}

	protected function authorizeAccess(): void
	{
		parent::authorizeAccess();
		if (!$this->gate->allows('view', $this->record)) abort(403);
		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) abort(403);
	}
PHP;
}

function getTitle(string $type): string
{
    if ($type === 'Create') return 'public function getTitle(): string { return __("Создать"); }';
    if ($type === 'Edit') return 'public function getTitle(): string { return __("Редактировать"); }';
    if ($type === 'List') return 'public function getTitle(): string { return __("Список"); }';
    return 'public function getTitle(): string { return __("Просмотр"); }';
}
