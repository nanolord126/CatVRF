<?php
declare(strict_types=1);

/**
 * ЭТАП 1: Аудит middleware архитектуры
 * Проверяем что нужно отрефакторить
 */

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

require __DIR__ . '/vendor/autoload.php';

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'phase' => 'ЭТАП 1: Middleware Refactor Audit',
    'target_middleware' => [
        'B2CB2BMiddleware',
        'FraudCheckMiddleware',
        'RateLimitingMiddleware',
        'AgeVerificationMiddleware',
        'CorrelationIdMiddleware',
    ],
    'findings' => [],
    'controllers_with_duplicated_code' => [],
    'routes_without_middleware' => [],
];

echo "\n=== ЭТАП 1: MIDDLEWARE REFACTOR AUDIT ===\n";

// ────────────────────────────────────────────────────────────────────
// 1. Проверим что middleware존재 и правильно реализованы
// ────────────────────────────────────────────────────────────────────

echo "\n[1/5] Checking middleware implementations...\n";

$middlewarePath = __DIR__ . '/app/Http/Middleware';
foreach ($report['target_middleware'] as $mw) {
    $file = "$middlewarePath/{$mw}.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $lines = count(explode("\n", $content));
        
        $hasHandle = strpos($content, 'public function handle') !== false;
        $hasNextMiddleware = strpos($content, '$next($request)') !== false;
        
        echo "  ✓ {$mw}.php ({$lines} lines)\n";
        
        $report['findings'][$mw] = [
            'exists' => true,
            'lines' => $lines,
            'has_handle' => $hasHandle,
            'has_next' => $hasNextMiddleware,
        ];
    } else {
        echo "  ✗ {$mw}.php NOT FOUND\n";
        $report['findings'][$mw] = ['exists' => false];
    }
}

// ────────────────────────────────────────────────────────────────────
// 2. Сканируем контроллеры на дублирующийся код
// ────────────────────────────────────────────────────────────────────

echo "\n[2/5] Scanning controllers for duplicated middleware logic...\n";

$patterns = [
    'fraud_check_duplicate' => [
        'pattern' => 'fraudControl|fraudControlService|$this->fraudControl',
        'description' => 'Fraud check logic duplicated in controller',
    ],
    'rate_limiting_duplicate' => [
        'pattern' => 'rateLimiter|ensureLimit|Rate',
        'description' => 'Rate limiting logic duplicated in controller',
    ],
    'correlation_id_duplicate' => [
        'pattern' => 'correlation_id|X-Correlation-ID|Str::uuid',
        'description' => 'Correlation ID generation duplicated in controller',
    ],
    'b2b_check_duplicate' => [
        'pattern' => 'b2c_mode|b2b_mode|isB2C|isB2B',
        'description' => 'B2B/B2C check logic duplicated in controller',
    ],
];

$finder = new Finder();
$finder->files()->in(__DIR__ . '/app/Http/Controllers/Api')->name('*.php')->notName('BaseApiController.php');

$controllers_count = 0;
$duplicated_patterns_count = 0;

foreach ($finder as $file) {
    $controllers_count++;
    $content = file_get_contents($file->getRealPath());
    
    // Skip if it's BaseApiController
    if (strpos($content, 'abstract class BaseApiController') !== false) {
        continue;
    }
    
    foreach ($patterns as $key => $pattern_info) {
        if (preg_match("/{$pattern_info['pattern']}/i", $content)) {
            $duplicated_patterns_count++;
            
            if (!isset($report['controllers_with_duplicated_code'][$file->getFilename()])) {
                $report['controllers_with_duplicated_code'][$file->getFilename()] = [];
            }
            
            $report['controllers_with_duplicated_code'][$file->getFilename()][] = [
                'pattern' => $key,
                'description' => $pattern_info['description'],
            ];
        }
    }
}

echo "  Found {$controllers_count} controllers\n";
echo "  {$duplicated_patterns_count} instances of duplicated middleware logic\n";
echo "  Controllers affected: " . count($report['controllers_with_duplicated_code']) . "\n";

// ────────────────────────────────────────────────────────────────────
// 3. Проверим BaseApiController
// ────────────────────────────────────────────────────────────────────

echo "\n[3/5] Analyzing BaseApiController...\n";

$baseFile = __DIR__ . '/app/Http/Controllers/Api/BaseApiController.php';
$baseContent = file_get_contents($baseFile);

$baseMethods = [];
if (preg_match_all('/protected\s+function\s+(\w+)\s*\(/i', $baseContent, $matches)) {
    $baseMethods = $matches[1];
}

echo "  Methods in BaseApiController:\n";
foreach ($baseMethods as $method) {
    echo "    - $method()\n";
}

$report['base_api_controller'] = [
    'methods' => $baseMethods,
    'lines' => count(explode("\n", $baseContent)),
];

// ────────────────────────────────────────────────────────────────────
// 4. Проверим registration в Kernel.php
// ────────────────────────────────────────────────────────────────────

echo "\n[4/5] Verifying middleware registration in Kernel.php...\n";

$kernelFile = __DIR__ . '/app/Http/Kernel.php';
$kernelContent = file_get_contents($kernelFile);

$required_aliases = [
    'correlation-id' => 'CorrelationIdMiddleware::class',
    'b2c-b2b' => 'B2CB2BMiddleware::class',
    'fraud-check' => 'FraudCheckMiddleware::class',
    'rate-limit' => 'RateLimitingMiddleware::class',
    'age-verify' => 'AgeVerificationMiddleware::class',
];

$report['kernel_registration'] = [];

foreach ($required_aliases as $alias => $class) {
    $pattern = "'{$alias}'.*{$class}";
    if (preg_match("/{$pattern}/", $kernelContent)) {
        echo "  ✓ '{$alias}' registered\n";
        $report['kernel_registration'][$alias] = true;
    } else {
        echo "  ✗ '{$alias}' NOT registered\n";
        $report['kernel_registration'][$alias] = false;
    }
}

// ────────────────────────────────────────────────────────────────────
// 5. Проверим routes
// ────────────────────────────────────────────────────────────────────

echo "\n[5/5] Analyzing route middleware...\n";

$apiFile = __DIR__ . '/routes/api.php';
$apiContent = file_get_contents($apiFile);

// Проверим наличие middleware в route groups
$middlewarePatterns = [
    'correlation-id' => 'correlation-id',
    'auth:sanctum' => 'auth:sanctum',
    'b2c-b2b' => 'b2c-b2b',
    'fraud-check' => 'fraud-check',
    'rate-limit' => 'rate-limit',
    'age-verify' => 'age-verify',
];

$report['routes_middleware'] = [];

foreach ($middlewarePatterns as $name => $pattern) {
    $count = substr_count($apiContent, $pattern);
    echo "  - '{$name}': used {$count} times\n";
    $report['routes_middleware'][$name] = $count;
}

// ────────────────────────────────────────────────────────────────────
// ВЫВОД ОТЧЕТА
// ────────────────────────────────────────────────────────────────────

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║            MIDDLEWARE REFACTOR AUDIT SUMMARY                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

echo "\n✓ MIDDLEWARE IMPLEMENTATIONS:\n";
foreach ($report['findings'] as $mw => $data) {
    if ($data['exists']) {
        echo "  ✓ {$mw}: OK ({$data['lines']} lines)\n";
    } else {
        echo "  ✗ {$mw}: MISSING\n";
    }
}

echo "\n✗ CONTROLLER ISSUES:\n";
echo "  - Total controllers: {$controllers_count}\n";
echo "  - Controllers with duplicated code: " . count($report['controllers_with_duplicated_code']) . "\n";
echo "  - Total duplicated patterns: {$duplicated_patterns_count}\n";

echo "\n⚠ BaseApiController:\n";
echo "  - Lines: " . $report['base_api_controller']['lines'] . "\n";
echo "  - Methods: " . count($report['base_api_controller']['methods']) . "\n";

echo "\n🔧 KERNEL.php REGISTRATION:\n";
$registered_count = array_sum($report['kernel_registration']);
echo "  - Registered: {$registered_count}/" . count($required_aliases) . "\n";

// ────────────────────────────────────────────────────────────────────
// SAVE JSON REPORT
// ────────────────────────────────────────────────────────────────────

file_put_contents(
    __DIR__ . '/MIDDLEWARE_REFACTOR_AUDIT.json',
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\n✓ Report saved to: MIDDLEWARE_REFACTOR_AUDIT.json\n\n";
