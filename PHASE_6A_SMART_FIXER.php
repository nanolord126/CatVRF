<?php

declare(strict_types=1);

/**
 * PHASE_6A_SMART_FIXER - Intelligent Page Fixer
 * 
 * Adds boot() and authorizeAccess() to 190+ pages in 3 minutes
 * Handles: Create, Edit, List, View pages
 * 
 * Usage: php PHASE_6A_SMART_FIXER.php
 */

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';
$alreadyFixed = ['RestaurantResource', 'HotelResource', 'SportEventResource', 'ConcertResource', 'FlowerResource'];

$fixed = 0;
$skipped = 0;
$errors = 0;

echo "🚀 Starting Phase 6A Smart Fixer\n";
echo "📂 Base directory: $baseDir\n\n";

// Scan all pages
$di = new RecursiveDirectoryIterator($baseDir);
$ri = new RecursiveIteratorIterator($di);
$patterns = new RegexIterator($ri, '/Marketplace\/([A-Z][a-zA-Z0-9]+)Resource\/Pages\/([A-Z][a-z]+Page|[A-Z][a-z]+)\.php$/', RegexIterator::GET_MATCH);

foreach ($patterns as $matches) {
    $resourceName = $matches[1];
    $pageName = $matches[2];
    $filePath = $matches[0][0];
    
    // Skip already-fixed resources
    if (in_array($resourceName . 'Resource', $alreadyFixed, true)) {
        $skipped++;
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Skip if already fixed
    if (strpos($content, 'public function boot(') !== false) {
        $skipped++;
        continue;
    }
    
    // Skip very large files
    if (strlen($content) > 2500) {
        $skipped++;
        continue;
    }
    
    try {
        $new = fixPageMinimally($content, $pageName);
        if ($new !== $content) {
            file_put_contents($filePath, $new);
            $fixed++;
            echo "✅ $resourceName / $pageName\n";
        }
    } catch (Exception $e) {
        $errors++;
        echo "❌ $resourceName / $pageName: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat('═', 80) . "\n";
echo "✨ COMPLETED\n";
echo "✅ Fixed: $fixed pages\n";
echo "⏭️  Skipped: $skipped pages\n";
echo "❌ Errors: $errors\n";

function fixPageMinimally(string $content, string $pageName): string
{
    // Add imports if missing
    $imports = [
        'Filament\\Actions' => 'use Filament\\Actions;',
        'Filament\\Notifications\\Notification' => 'use Filament\\Notifications\\Notification;',
        'Illuminate\\Contracts\\Auth\\Guard' => 'use Illuminate\\Contracts\\Auth\\Guard;',
        'Illuminate\\Contracts\\Auth\\Access\\Gate' => 'use Illuminate\\Contracts\\Auth\\Access\\Gate;',
        'Illuminate\\Database\\DatabaseManager' => 'use Illuminate\\Database\\DatabaseManager;',
        'Illuminate\\Database\\Eloquent\\Model' => 'use Illuminate\\Database\\Eloquent\\Model;',
        'Illuminate\\Http\\Request' => 'use Illuminate\\Http\\Request;',
        'Illuminate\\Log\\LogManager' => 'use Illuminate\\Log\\LogManager;',
        'Illuminate\\Support\\Str' => 'use Illuminate\\Support\\Str;',
        'Throwable' => 'use Throwable;',
    ];
    
    foreach ($imports as $import) {
        if (strpos($content, $import) === false) {
            // Add after last use statement
            $pattern = '/^(use .+;)$/m';
            if (preg_match_all($pattern, $content, $matches)) {
                $lastUse = end($matches[1]);
                $pos = strpos($content, $lastUse) + strlen($lastUse);
                $content = substr_replace($content, "\r\n$import", $pos, 0);
            }
        }
    }
    
    // Find class body
    if (!preg_match('/^final class \w+ extends (\w+)/m', $content, $m)) {
        throw new Exception('Cannot find class definition');
    }
    
    $parentClass = $m[1];
    
    // Find insertion point (before getHeaderActions)
    $headerActionsPos = strpos($content, 'protected function getHeaderActions');
    if ($headerActionsPos === false) {
        $headerActionsPos = strpos($content, 'public function getHeaderActions');
    }
    
    if ($headerActionsPos === false) {
        // Try to insert before last closing brace
        $headerActionsPos = strrpos($content, '}') - 1;
    }
    
    if ($headerActionsPos === false) {
        throw new Exception('Cannot find insertion point');
    }
    
    // Generate boot method
    $pageType = determineType($pageName);
    $bootMethod = generateBootMethod($pageType);
    
    // Insert boot method
    $tabs = "\r\n\r\n\t";
    $content = substr_replace($content, $tabs . $bootMethod . "\r\n", $headerActionsPos, 0);
    
    // Add getTitle if missing
    if (strpos($content, 'public function getTitle()') === false) {
        $closingBrace = strrpos($content, '}');
        $title = generateGetTitle($pageName);
        $content = substr_replace($content, "\r\n\r\n\t" . $title . "\r\n", $closingBrace, 0);
    }
    
    // Set proper line endings and BOM
    $content = str_replace("\r\n\r\n\r\n", "\r\n\r\n", $content); // Remove triple newlines
    
    return $content;
}

function determineType(string $name): string
{
    if (str_contains($name, 'Create')) return 'Create';
    if (str_contains($name, 'Edit')) return 'Edit';
    if (str_contains($name, 'List')) return 'List';
    return 'View';
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
			$this->log->channel('audit')->info('List accessed', [
				'user_id' => $user->id,
				'ip' => $this->request->ip(),
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

function generateGetTitle(string $name): string
{
    if (str_contains($name, 'Create')) {
        return 'public function getTitle(): string { return __("Создать"); }';
    }
    if (str_contains($name, 'Edit')) {
        return 'public function getTitle(): string { return __("Редактировать"); }';
    }
    if (str_contains($name, 'List')) {
        return 'public function getTitle(): string { return __("Список"); }';
    }
    return 'public function getTitle(): string { return __("Просмотр"); }';
}
