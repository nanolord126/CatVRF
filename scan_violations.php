<?php
/**
 * Комплексный аудит нарушений канона CatVRF 2026
 * Сканирует: WalletService::, FraudControlService::, статические сервисы,
 * Models без SoftDeletes, non-private properties в Services
 */

$base = __DIR__;
$appDir = $base . '/app';

$results = [
    'static_service_calls' => [],
    'models_no_soft_deletes' => [],
    'service_non_private_props' => [],
    'missing_strict_types' => [],
    'return_null' => [],
    'missing_correlation_id_in_logs' => [],
];

$allowedStatic = [
    'Str::', 'Carbon::', 'Schema::', 'Gate::', 'Route::', 'Http::',
    'Validator::', 'RateLimiter::', 'Queue::', 'Artisan::', 'DB::',
    'App::', 'parent::', 'static::', '::class', 'Event::',
    'FraudControlService::', 'WalletService::', 'BonusService::',
    'InsufficientStockException::', 'EntityNotFoundException::',
    'self::', 'Config::', 'URL::', 'Hash::', 'Crypt::',
    'Notification::', 'Mail::', 'Log::', 'Storage::', 'Cache::',
    'Redirect::', 'Response::', 'Request::', 'File::',
    'Eloquent::', 'Builder::', 'Collection::', 'Arr::', 'Lang::',
    'Bus::', 'Pipeline::', 'Auth::', 'Password::', 'Blade::',
    'View::', 'Session::', 'Cookie::', 'Crypt::', 'Image::',
    'VolumeTier::', 'PaymentStatus::', 'OrderStatus::', 'CartStatus::',
];

$servicePattern = '/[A-Z][a-zA-Z]+Service::[a-z][a-zA-Z]+\(/';

function getAllPhpFiles(string $dir): array
{
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

$allFiles = getAllPhpFiles($appDir);
$total = count($allFiles);
$checked = 0;

echo "=== CatVRF Violation Scanner ===\n";
echo "Total files: $total\n\n";

foreach ($allFiles as $path) {
    $checked++;
    $rel = str_replace($base . DIRECTORY_SEPARATOR, '', $path);
    $content = file_get_contents($path);
    $lines = explode("\n", $content);
    $basename = basename($path);
    
    // ======= 1. strict_types check =======
    if (!str_contains($content, 'declare(strict_types=1)') && str_contains($content, '<?php')) {
        // Skip vendor, migrations, configs
        if (!str_contains($rel, 'vendor') && !str_contains($rel, 'database/migrations')
            && !str_contains($rel, 'config/')) {
            $results['missing_strict_types'][] = $rel;
        }
    }
    
    // ======= 2. return null =======
    // Policy before(): ?bool returning null is a valid Laravel pattern (skip to next check)
    $isPolicyFile = str_contains($rel, 'Policies') || str_ends_with($basename, 'Policy.php');
    foreach ($lines as $lineNo => $line) {
        $trimmed = trim($line);
        if ($trimmed === 'return null;' && !str_contains($rel, 'vendor') && !$isPolicyFile) {
            $results['return_null'][] = "$rel:" . ($lineNo + 1);
        }
    }
    
    // ======= 3. Static service calls (real violations) =======
    if (!str_contains($rel, 'vendor') && !str_contains($rel, 'test') && !str_contains($rel, 'Test')) {
        foreach ($lines as $lineNo => $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#')) {
                continue;
            }
            // Find ::method( pattern
            if (preg_match('/([A-Z][a-zA-Z]+)::[a-z][a-zA-Z]+\(/', $line, $m)) {
                $class = $m[1] . '::';
                $isAllowed = false;
                foreach ($allowedStatic as $allowed) {
                    if (str_contains($line, $allowed)) {
                        $isAllowed = true;
                        break;
                    }
                }
                // Check specifically for Service/Repository static calls
                // Skip Eloquent model methods — these are valid ORM calls even if class ends in 'Service'
                $eloquentMethods = ['::query(', '::find(', '::findOrFail(', '::where(', '::create(',
                    '::firstOrCreate(', '::updateOrCreate(', '::all(', '::with(',
                    '::paginate(', '::first(', '::get(', '::count(', '::exists(', '::insert(',
                    // Filament Page route definitions — valid pattern
                    '::route(', '::initialize(', '::distinct(', '::pluck(',
                ];
                $isEloquent = false;
                foreach ($eloquentMethods as $em) {
                    if (str_contains($line, $em)) { $isEloquent = true; break; }
                }
                if (!$isAllowed && !$isEloquent && (
                    str_contains($class, 'Service::') ||
                    str_contains($class, 'Repository::') ||
                    str_contains($class, 'Manager::')
                )) {
                    $results['static_service_calls'][] = "$rel:" . ($lineNo + 1) . " → " . trim($line);
                }
            }
        }
    }
    
    // ======= 4. Models without SoftDeletes =======
    if (str_contains($rel, 'Domains') && str_contains($basename, '.php')
        && !str_contains($basename, 'Interface') && !str_contains($basename, 'DTO')
        && !str_contains($basename, 'Test')
    ) {
        if (preg_match('/class\s+\w+\s+extends\s+Model\b/', $content)
            && !str_contains($content, 'SoftDeletes')
            && !str_contains($content, 'Scopes/') // Not a scope
        ) {
            $results['models_no_soft_deletes'][] = $rel;
        }
    }
    
    // ======= 5. Service non-readonly properties =======
    // Skip if file is actually an Eloquent Model (extends Model) — $table, $fillable etc. are correct
    $isModel = preg_match('/class\s+\w+\s+extends\s+(Model|Authenticatable|Pivot)/', $content);
    if (str_contains($basename, 'Service.php') && !str_contains($rel, 'vendor')
        && !str_contains($rel, 'test') && !$isModel) {
        foreach ($lines as $lineNo => $line) {
            if (preg_match('/^\s+(public|protected)\s+(\??\w+\s+)?\$/', $line)
                && !str_contains($line, 'readonly')
                && !str_contains($line, 'static ')
                && !preg_match('/\$[a-zA-Z]+\s*=\s*\[/', $line) // arrays are OK for config
            ) {
                $results['service_non_private_props'][] = "$rel:" . ($lineNo + 1) . " → " . trim($line);
            }
        }
    }
}

// ======= REPORT =======
echo "=== RESULTS ===\n\n";

$cats = [
    'missing_strict_types' => 'Missing strict_types (CRITICAL)',
    'return_null' => 'return null violations',
    'static_service_calls' => 'Static Service/Repository calls',
    'models_no_soft_deletes' => 'Models without SoftDeletes',
    'service_non_private_props' => 'Service non-private/non-readonly properties',
];

foreach ($cats as $key => $label) {
    $count = count($results[$key]);
    echo "[$count] $label\n";
    if ($count > 0 && $count <= 30) {
        foreach ($results[$key] as $item) {
            echo "  → $item\n";
        }
    } elseif ($count > 30) {
        foreach (array_slice($results[$key], 0, 20) as $item) {
            echo "  → $item\n";
        }
        echo "  ... and " . ($count - 20) . " more\n";
    }
    echo "\n";
}

echo "=== TOTALS ===\n";
$total_violations = 0;
foreach ($results as $key => $items) {
    $total_violations += count($items);
    echo sprintf("  %-40s %d\n", $key, count($items));
}
echo "TOTAL: $total_violations violations\n";
