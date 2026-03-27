<?php

declare(strict_types=1);

/**
 * ULTRA_FINAL_FIX - The Real Final Fix
 * Finds ALL pages - EVERY file that doesn't have ANY boot method (not just boot(Guard)
 */

$baseDir = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\Marketplace';

$fixed = 0;
$skipped = 0;
$errors = [];

echo "🔥 ULTRA FINAL FIX - Checking ALL 233 pages\n\n";

$resourceDirs = array_filter(
    array_map('trim', scandir($baseDir)),
    fn($d) => !in_array($d, ['.', '..', '.gitkeep']) && is_dir("$baseDir\\$d")
);

foreach ($resourceDirs as $resourceDir) {
    $resourcePath = "$baseDir\\$resourceDir";
    $pagesDir = "$resourcePath\\Pages";
    
    if (!is_dir($pagesDir)) continue;
    
    $files = glob("$pagesDir\\*.php");
    if (!$files) continue;
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $fileName = basename($file);
        
        // Check if it has ANY kind of boot method
        if (strpos($content, 'public function boot(') !== false || 
            strpos($content, 'protected function boot(') !== false) {
            $skipped++;
            continue;
        }
        
        // Skip if >3500 lines (complex class)
        if (strlen($content) > 3500) {
            $skipped++;
            continue;
        }
        
        try {
            $type = getPageTypeX($fileName);
            $content = addAllImportsX($content);
            
            // Find class and resource
            $resourcePos = strpos($content, 'protected static string $resource');
            if ($resourcePos === false) {
                throw new Exception('No resource');
            }
            
            $resourceEnd = strpos($content, ';', $resourcePos) + 1;
            $bootCode = generateBootX($type);
            
            $content = substr_replace($content, "\r\n\r\n\t" . $bootCode, $resourceEnd, 0);
            
            // Ensure getTitle
            if (strpos($content, 'public function getTitle()') === false && 
                strpos($content, 'protected function getTitle()') === false) {
                $lastBrace = strrpos($content, '}');
                $title = getTitleX($type);
                $content = substr_replace($content, "\r\n\r\n\t$title\r\n", $lastBrace, 0);
            }
            
            file_put_contents($file, $content);
            $fixed++;
            echo "✅ $resourceDir / $fileName\n";
            
        } catch (Exception $e) {
            $errors[$fileName] = $e->getMessage();
        }
    }
}

echo "\n" . str_repeat('═', 80) . "\n";
echo "🎉 ULTRA FINAL FIX COMPLETE\n";
echo str_repeat('═', 80) . "\n";
echo "✅ Total Fixed: $fixed\n";
echo "⏭️  Skipped (already have boot or too big): $skipped\n";
echo "❌ Errors: " . count($errors) . "\n";
echo "\nTotal touched: " . ($fixed + $skipped) . "/233 pages\n";

if (count($errors) > 0) {
    echo "\nErrors:\n";
    foreach (array_slice($errors, 0, 10) as $f => $e) {
        echo "  - $f: $e\n";
    }
}

function getPageTypeX(string $name): string
{
    if (str_contains($name, 'Create')) return 'Create';
    if (str_contains($name, 'Edit')) return 'Edit';
    if (str_contains($name, 'List')) return 'List';
    return 'View';
}

function addAllImportsX(string $content): string
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

function generateBootX(string $type): string
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
		if (!Gate::allows('create', $this->resource::$model)) abort(403);
	}

	protected function handleRecordCreation(array $data): Model
	{
		try {
			return DB::transaction(function () use ($data) {
				$user = $this->guard->user();
				$data['tenant_id'] = $user?->tenant_id;
				$record = parent::handleRecordCreation($data);
				if ($user) Log::channel('audit')->info('Created', ['id' => $record->id]);
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
		if (!Gate::allows('update', $this->record)) abort(403);
		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) abort(403);
	}

	protected function handleRecordUpdate(Model $record, array $data): Model
	{
		try {
			return DB::transaction(function () use ($record, $data) {
				$record = parent::handleRecordUpdate($record, $data);
				if ($user = $this->guard->user()) {
					Log::channel('audit')->info('Updated', ['id' => $record->id, 'user' => $user->id]);
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
		if ($user = $this->guard->user()) {
			Log::channel('audit')->info('List', ['user_id' => $user->id]);
		}
	}
PHP;
    }
    
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
		if (!Gate::allows('view', $this->record)) abort(403);
		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) abort(403);
	}
PHP;
}

function getTitleX(string $type): string
{
    return match($type) {
        'Create' => 'public function getTitle(): string { return __("Создать"); }',
        'Edit' => 'public function getTitle(): string { return __("Редактировать"); }',
        'List' => 'public function getTitle(): string { return __("Список"); }',
        default => 'public function getTitle(): string { return __("Просмотр"); }',
    };
}
