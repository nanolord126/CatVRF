<?php

declare(strict_types=1);

/**
 * FINAL_PHASE_6A_AUTOMATION
 * Fixes all 213 remaining empty/minimal pages
 */

$baseDir = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\Marketplace';
$alreadyFixed = ['Restaurant', 'Hotel', 'SportEvent', 'Concert', 'Flower'];

$fixed = [];
$errors = [];
$skipped = [];

// Manually build list of all resource directories
$resourceDirs = array_filter(array_map('trim', scandir($baseDir)), fn($d) => !in_array($d, ['.', '..', '.gitkeep']));

foreach ($resourceDirs as $resourceDir) {
    $resourcePath = $baseDir . '\\' . $resourceDir;
    
    if (!is_dir($resourcePath)) continue;
    
    // Check if this resource was already fixed
    $isFixed = false;
    foreach ($alreadyFixed as $fix) {
        if (str_contains($resourceDir, $fix)) {
            $isFixed = true;
            break;
        }
    }
    
    if ($isFixed) {
        echo "⏭️  Skipping $resourceDir (already fixed manually)\n";
        continue;
    }
    
    $pagesDir = $resourcePath . '\\Pages';
    if (!is_dir($pagesDir)) {
        continue;
    }
    
    // Get all .php files in Pages directory
    $pageFiles = glob($pagesDir . '\\*.php');
    if (!$pageFiles) continue;
    
    foreach ($pageFiles as $file) {
        $content = file_get_contents($file);
        $fileName = basename($file);
        
        // Skip if already has boot() method
        if (str_contains($content, 'public function boot(')) {
            $skipped[] = $fileName;
            continue;
        }
        
        // Skip large files (>2000 chars likely complex)
        if (strlen($content) > 2000) {
            $skipped[] = $fileName;
            continue;
        }
        
        try {
            $newContent = applyFixToPage($content, $fileName);
            
            // Write with CRLF endings
            file_put_contents($file, str_replace("\r\n\r\n\r\n", "\r\n\r\n", $newContent));
            
            $fixed[] = $resourceDir . '\\' . $fileName;
            echo "✅ $resourceDir / $fileName\n";
            
        } catch (Exception $e) {
            $errors[$fileName] = $e->getMessage();
            echo "❌ $resourceDir / $fileName: {$e->getMessage()}\n";
        }
    }
}

echo "\n" . str_repeat('═', 100) . "\n";
echo "✨ PHASE 6A AUTOMATION COMPLETE\n";
echo str_repeat('═', 100) . "\n";
echo "✅ Pages fixed: " . count($fixed) . "\n";
echo "⏭️  Already fixed or skipped: " . count($skipped) . "\n";
echo "❌ Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n⚠️  ERRORS:\n";
    foreach ($errors as $file => $msg) {
        echo "  - $file: $msg\n";
    }
}

echo "\n✨ Ready for testing!\n";

function applyFixToPage(string $content, string $fileName): string
{
    // Determine page type
    $type = 'List';
    if (str_contains($fileName, 'Create')) $type = 'Create';
    elseif (str_contains($fileName, 'Edit')) $type = 'Edit';
    elseif (str_contains($fileName, 'View') || str_contains($fileName, 'Show')) $type = 'View';
    
    // Get necessary imports
    $requiredImports = getRequiredImports($type);
    
    // Add missing imports
    foreach ($requiredImports as $import) {
        if (!str_contains($content, "use $import;")) {
            $lastUsePos = strrpos($content, 'use ');
            if ($lastUsePos !== false) {
                $endUsePos = strpos($content, ';', $lastUsePos) + 1;
                $content = substr_replace($content, "\r\nuse $import;", $endUsePos, 0);
            }
        }
    }
    
    // Generate boot method
    $bootMethod = generateBootMethod($type);
    
    // Find insertion point - before getHeaderActions
    $headerPos = strpos($content, 'protected function getHeaderActions');
    if ($headerPos === false) {
        $headerPos = strpos($content, 'public function getHeaderActions');
    }
    
    if ($headerPos !== false) {
        // Insert before getHeaderActions
        $indent = "\r\n\r\n\t";
        $content = substr_replace($content, $indent . $bootMethod . "\r\n", $headerPos, 0);
    }
    
    // Add getTitle() if missing
    if (!str_contains($content, 'public function getTitle()') && !str_contains($content, 'protected function getTitle()')) {
        $closingBrace = strrpos($content, '}');
        $title = getGetTitleMethod($type);
        $content = substr_replace($content, "\r\n\r\n\t" . $title . "\r\n", $closingBrace, 0);
    }
    
    return $content;
}

function getRequiredImports(string $type): array
{
    $base = [
        'Filament\\Notifications\\Notification',
        'Illuminate\\Contracts\\Auth\\Guard',
        'Illuminate\\Contracts\\Auth\\Access\\Gate',
        'Illuminate\\Http\\Request',
        'Illuminate\\Log\\LogManager',
        'Throwable',
    ];
    
    if ($type === 'Create' || $type === 'Edit') {
        array_push($base,
            'Illuminate\\Database\\DatabaseManager',
            'Illuminate\\Database\\Eloquent\\Model',
            'Illuminate\\Support\\Str'
        );
    }
    
    return $base;
}

function generateBootMethod(string $type): string
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
			$this->log->channel('audit')->info('List accessed', [
				'user_id' => $user->id,
				'tenant_id' => filament()->getTenant()?->id,
				'ip' => $this->request->ip(),
				'correlation_id' => $correlationId,
			]);
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
			abort(403, __('Unauthorized'));
		}

		if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) {
			abort(403, __('Forbidden'));
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
				$record = parent::handleRecordUpdate($record, $data);
				$user = $this->guard->user();
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
			Notification::make()->danger()->title(__('Ошибка'))->send();
			throw $e;
		}
	}
PHP;
}

function getGetTitleMethod(string $type): string
{
    if ($type === 'Create') return 'public function getTitle(): string { return __("Создать"); }';
    if ($type === 'Edit') return 'public function getTitle(): string { return __("Редактировать"); }';
    if ($type === 'List') return 'public function getTitle(): string { return __("Список"); }';
    return 'public function getTitle(): string { return __("Просмотр"); }';
}
