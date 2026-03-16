<?php

declare(strict_types=1);

/**
 * FIX_INCOMPLETE_PAGES
 * Fixes pages that had imports added but boot() method NOT inserted
 */

$filesToFix = [
    'c:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\Marketplace\\AnimalProductResource\\Pages\\CreateAnimalProduct.php',
    'c:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\Marketplace\\AnimalProductResource\\Pages\\EditAnimalProduct.php',
    'c:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\Marketplace\\AnimalProductResource\\Pages\\ViewAnimalProduct.php',
    'c:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\Marketplace\\AnimalProductResource\\Pages\\ListAnimalProducts.php',
];

foreach ($filesToFix as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if it ONLY has imports but no boot() method
    if (strpos($content, 'public function boot(Guard') === false && 
        strpos($content, 'use Illuminate\\Contracts\\Auth\\Guard;') !== false) {
        
        // This file needs the boot() method
        $type = determineType(basename($file));
        $newContent = insertBootMethod($content, $type);
        
        file_put_contents($file, $newContent);
        echo "✅ Fixed: " . basename($file) . "\n";
    } else {
        echo "⏭️  Skipped: " . basename($file) . " (already has boot or no imports)\n";
    }
}

function determineType(string $name): string
{
    if (str_contains($name, 'Create')) return 'Create';
    if (str_contains($name, 'Edit')) return 'Edit';
    if (str_contains($name, 'List')) return 'List';
    return 'View';
}

function insertBootMethod(string $content, string $type): string
{
    // Find resource line
    $resourceLinePos = strpos($content, 'protected static string $resource');
    if ($resourceLinePos === false) {
        return $content;
    }
    
    // Find end of resource line
    $resourceLineEnd = strpos($content, ';', $resourceLinePos) + 1;
    
    // Find getTitle method if exists
    $getTitlePos = strpos($content, 'public function getTitle()');
    
    if ($getTitlePos !== false) {
        // Insert before getTitle
        $insertPos = $getTitlePos;
    } else {
        // Insert before last closing brace
        $insertPos = strrpos($content, '}') - 1;
    }
    
    $bootCode = getBoot($type);
    $content = substr_replace($content, "\r\n\r\n\t" . $bootCode . "\r\n", $insertPos, 0);
    
    return $content;
}

function getBoot(string $type): string
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
					$this->log->channel('audit')->info('Created', ['id' => $record->id]);
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

echo "\n✨ Done fixing incomplete pages\n";
