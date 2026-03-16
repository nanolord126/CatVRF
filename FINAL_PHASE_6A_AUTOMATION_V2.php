<?php

declare(strict_types=1);

/**
 * FINAL_PHASE_6A_AUTOMATION_V2
 * Fixed version that properly inserts boot() and authorizeAccess() methods
 */

$baseDir = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\Marketplace';
$alreadyFixed = ['Restaurant', 'Hotel', 'SportEvent', 'Concert', 'Flower', 'CountryEstate'];

$fixed = [];
$errors = [];
$alreadyHas = [];

$resourceDirs = array_filter(array_map('trim', scandir($baseDir)), fn($d) => !in_array($d, ['.', '..', '.gitkeep']));

foreach ($resourceDirs as $resourceDir) {
    $resourcePath = $baseDir . '\\' . $resourceDir;
    
    if (!is_dir($resourcePath)) continue;
    
    // Check if already fixed
    $isFixed = false;
    foreach ($alreadyFixed as $fix) {
        if (str_contains($resourceDir, $fix)) {
            $isFixed = true;
            break;
        }
    }
    
    if ($isFixed) {
        continue;
    }
    
    $pagesDir = $resourcePath . '\\Pages';
    if (!is_dir($pagesDir)) continue;
    
    $pageFiles = glob($pagesDir . '\\*.php');
    if (!$pageFiles) continue;
    
    foreach ($pageFiles as $file) {
        $content = file_get_contents($file);
        $fileName = basename($file);
        
        // Skip if already has proper boot() method
        if (strpos($content, 'public function boot(Guard') !== false) {
            $alreadyHas[] = $fileName;
            continue;
        }
        
        // Skip large files
        if (strlen($content) > 2000) {
            continue;
        }
        
        try {
            $newContent = fixPageProperly($content, $fileName);
            
            if ($newContent !== $content) {
                file_put_contents($file, $newContent);
                $fixed[] = $resourceDir . '\\' . $fileName;
                echo "✅ Fixed: $resourceDir / $fileName\n";
            }
            
        } catch (Exception $e) {
            $errors[$fileName] = $e->getMessage();
        }
    }
}

echo "\n" . str_repeat('═', 100) . "\n";
echo "✨ PHASE 6A AUTOMATION V2 COMPLETE\n";
echo str_repeat('═', 100) . "\n";
echo "✅ Fixed: " . count($fixed) . " pages\n";
echo "⏭️  Already had boot(): " . count($alreadyHas) . " pages\n";
echo "❌ Errors: " . count($errors) . " pages\n";

if (!empty($errors)) {
    echo "\n⚠️  ERRORS:\n";
    foreach ($errors as $file => $msg) {
        echo "  - $file: $msg\n";
    }
}

function fixPageProperly(string $content, string $fileName): string
{
    // Only fix if it doesn't already have boot() with Guard param
    if (strpos($content, 'public function boot(Guard') !== false) {
        return $content;
    }
    
    $type = determineType($fileName);
    
    // Add necessary imports first
    $imports = getImportsNeeded($type);
    foreach ($imports as $imp) {
        if (strpos($content, "use $imp;") === false) {
            // Find the last use statement and add after it
            if (preg_match_all('/(^use .+;$)/m', $content, $matches)) {
                $lastUse = end($matches[0]);
                $pos = strpos($content, $lastUse) + strlen($lastUse);
                $content = substr_replace($content, "\r\nuse $imp;", $pos, 0);
            }
        }
    }
    
    // Find class declaration
    if (!preg_match('/^(final class \w+ extends \w+\s*\{)/m', $content, $matches)) {
        throw new Exception('Cannot find class');
    }
    
    $classDecl = $matches[1];
    $classPos = strpos($content, $classDecl);
    if ($classPos === false) throw new Exception('Class not found');
    
    $bodyStart = $classPos + strlen($classDecl);
    
    // Find protected static $resource line
    $resourceLinePos = strpos($content, 'protected static string $resource', $bodyStart);
    if ($resourceLinePos === false) {
        throw new Exception('Cannot find resource property');
    }
    
    // Find end of that line
    $resourceLineEnd = strpos($content, ';', $resourceLinePos) + 1;
    
    // Find getHeaderActions
    $getHeaderPos = strpos($content, 'protected function getHeaderActions', $resourceLineEnd);
    if ($getHeaderPos === false) {
        $getHeaderPos = strpos($content, 'public function getHeaderActions', $resourceLineEnd);
    }
    
    if ($getHeaderPos === false) {
        throw new Exception('No getHeaderActions found');
    }
    
    // Generate boot and insert before getHeaderActions
    $bootCode = generateBootAndAuth($type);
    $insertPos = $getHeaderPos;
    
    $content = substr_replace($content, "\r\n\r\n\t$bootCode\r\n", $insertPos, 0);
    
    // Add getTitle if missing
    if (strpos($content, 'public function getTitle()') === false) {
        $lastBrace = strrpos($content, '}');
        $title = getTitle($type);
        $content = substr_replace($content, "\r\n\r\n\t$title\r\n", $lastBrace, 0);
    }
    
    return $content;
}

function determineType(string $name): string
{
    if (str_contains($name, 'Create')) return 'Create';
    if (str_contains($name, 'Edit')) return 'Edit';
    if (str_contains($name, 'List')) return 'List';
    return 'View';
}

function getImportsNeeded(string $type): array
{
    $base = [
        'Filament\\Notifications\\Notification',
        'Illuminate\\Contracts\\Auth\\Guard',
        'Illuminate\\Contracts\\Auth\\Access\\Gate',
        'Illuminate\\Http\\Request',
        'Illuminate\\Log\\LogManager',
        'Throwable',
    ];
    
    if (in_array($type, ['Create', 'Edit'])) {
        array_push($base,
            'Illuminate\\Database\\DatabaseManager',
            'Illuminate\\Database\\Eloquent\\Model',
            'Illuminate\\Support\\Str'
        );
    }
    
    return $base;
}

function generateBootAndAuth(string $type): string
{
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
			$correlationId = $this->request->header('X-Correlation-ID') ?? uniqid();
			$this->log->channel('audit')->info('List', ['user_id' => $user->id]);
		}
	}
PHP;
    }
    
    if ($type === 'View') {
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
		if (!$this->gate->allows('view', $this->record)) {
			abort(403);
		}
		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) {
			abort(403);
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
		if (!$this->gate->allows('update', $this->record)) {
			abort(403);
		}
		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) {
			abort(403);
		}
	}

	protected function handleRecordUpdate(Model $record, array $data): Model
	{
		try {
			return $this->db->transaction(function () use ($record, $data) {
				$record = parent::handleRecordUpdate($record, $data);
				$user = $this->guard->user();
				if ($user) {
					$this->log->channel('audit')->info('Updated', ['id' => $record->id, 'user_id' => $user->id]);
				}
				Notification::make()->success()->title(__('Обновлено'))->send();
				return $record;
			});
		} catch (Throwable $e) {
			Notification::make()->danger()->title(__('Ошибка'))->send();
			throw $e;
		}
	}
PHP;
    }
    
    // Create
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
					$this->log->channel('audit')->info('Created', ['id' => $record->id, 'user_id' => $user->id]);
				}
				Notification::make()->success()->title(__('Создано'))->send();
				return $record;
			});
		} catch (Throwable $e) {
			Notification::make()->danger()->title(__('Ошибка'))->send();
			throw $e;
		}
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
