<?php declare(strict_types=1);

/**
 * Create missing route files
 */

$routes = [
    'photography',
    'grocery',
    'healthy_food',
    'confectionery',
    'office_catering',
];

$template = <<<'PHP'
<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * %s API Routes v1
 * Production 2026.03.25
 */

Route::middleware(['api'])->prefix('api/v1/%s')->group(function () {
    // Placeholder routes
    Route::get('/', function () {
        return response()->json(['message' => '%s API - Coming soon']);
    });
});
PHP;

foreach ($routes as $route) {
    $file = __DIR__ . '/routes/' . $route . '.api.php';
    if (!file_exists($file)) {
        $name = str_replace('_', ' ', ucwords($route, '_'));
        $content = sprintf($template, $name, $route, $name);
        file_put_contents($file, $content);
        echo "✅ Created: $route.api.php\n";
    }
}

echo "\n✅ All missing route files created!\n";
