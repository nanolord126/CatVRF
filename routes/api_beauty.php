<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/health', static fn () => response()->json([
    'status' => 'ok',
    'scope' => 'beauty-only',
]));

$beautyRouteFiles = [
    __DIR__ . '/beauty.api.php',
    __DIR__ . '/beauty.api.corrected.php',
    __DIR__ . '/b2b.beauty.api.php',
    __DIR__ . '/beauty.panel.api.php',
    __DIR__ . '/api/beauty.php',
];

foreach ($beautyRouteFiles as $beautyRouteFile) {
    if (is_file($beautyRouteFile)) {
        require $beautyRouteFile;
    }
}
