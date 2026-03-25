<?php declare(strict_types=1);

/**
 * Create all missing route files
 */

$apiFile = file_get_contents(__DIR__ . '/routes/api.php');

// Find all require statements
preg_match_all("/require __DIR__ \. '\/(\w+)\.api\.php'/", $apiFile, $matches);
$required = $matches[1] ?? [];

$existing = array_map(
    fn($f) => basename($f, '.api.php'),
    glob(__DIR__ . '/routes/*.api.php')
);

$missing = array_diff($required, $existing);

$template = <<<'PHP'
<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * %s API Routes v1
 * Production 2026.03.25
 */

Route::middleware(['api'])->prefix('api/v1/%s')->group(function () {
    Route::get('/', fn() => response()->json(['message' => '%s API - Coming soon']));
});
PHP;

foreach ($missing as $route) {
    $file = __DIR__ . '/routes/' . $route . '.api.php';
    $name = str_replace('_', ' ', ucwords($route, '_'));
    $content = sprintf($template, $name, $route, $name);
    file_put_contents($file, $content);
    echo "✅ Created: $route.api.php\n";
}

if (empty($missing)) {
    echo "✅ All required route files exist!\n";
} else {
    echo "\n✅ Created " . count($missing) . " missing files\n";
}
