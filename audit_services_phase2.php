<?php declare(strict_types=1);

/**
 * PHASE 2: Services Quality Audit & Fix Script
 * Проверка и исправление сервисов согласно КАНОНУ 2026
 * 
 * Проверяет:
 * - DB::transaction() в критичных методах
 * - Log::channel('audit') везде
 * - Исключения вместо return false/null/[]
 * - correlation_id во всех логах
 */

$basePath = 'c:\\opt\\kotvrf\\CatVRF';
$servicesPath = $basePath . '\\app\\Services';
$domainServicesPath = $basePath . '\\app\\Domains';

// TIER 1: Критичные сервисы
$tier1Services = [
    'WalletService.php',
    'PaymentGatewayService.php',
    'PromoService.php',
    'ReferralService.php',
    'Fraud\\FraudMLService.php',
    'Security\\IdempotencyService.php',
    'Inventory\\InventoryService.php',
];

$issues = [];
$fixed = [];
$warnings = [];

echo "════════════════════════════════════════════════════════════════\n";
echo "🔍 ФАЗА 2: АУДИТ КАЧЕСТВА СЕРВИСОВ\n";
echo "════════════════════════════════════════════════════════════════\n\n";

function checkService($filePath, $serviceName, &$issues, &$warnings) {
    if (!file_exists($filePath)) {
        echo "⏭️  ПРОПУЩЕН (не найден): $serviceName\n";
        return;
    }

    $content = file_get_contents($filePath);
    $problems = [];

    echo "📋 Проверяю: $serviceName\n";

    // Проверка 1: declare(strict_types=1)
    if (!str_contains($content, 'declare(strict_types=1)')) {
        $problems[] = '❌ Отсутствует declare(strict_types=1)';
    } else {
        echo "  ✅ declare(strict_types=1) есть\n";
    }

    // Проверка 2: DB::transaction() в критичных методах
    preg_match_all('/public function (debit|credit|hold|apply|init|create|update|delete)\(/', $content, $matches);
    $mutationMethods = array_unique($matches[1]);

    foreach ($mutationMethods as $method) {
        $methodPattern = "public function $method\\([^}]*?\\{";
        if (preg_match($methodPattern, $content, $methodMatch)) {
            $methodBody = substr($content, strpos($content, $methodMatch[0]));
            $methodBody = substr($methodBody, 0, strpos($methodBody, "}\n\n") ?: strpos($methodBody, "}\n    }"));

            if (!str_contains($methodBody, 'DB::transaction(')) {
                $problems[] = "⚠️  Метод $method() не использует DB::transaction()";
                $warnings["$serviceName::$method()"] = 'DB::transaction() отсутствует';
            }
        }
    }

    // Проверка 3: return null/false/[] вместо throw
    $badReturns = [
        'return null;' => 'return null вместо throw',
        'return false;' => 'return false вместо throw',
        'return [];' => 'return [] вместо throw',
        'return collect();' => 'return collect() вместо throw',
    ];

    foreach ($badReturns as $pattern => $msg) {
        if (str_contains($content, $pattern)) {
            preg_match_all('/public function [a-zA-Z_]+\([^)]*\)[^{]*\{[^}]*' . preg_quote($pattern) . '/', $content, $matches);
            if (!empty($matches[0])) {
                $problems[] = "❌ Найдено: $msg (" . count($matches[0]) . " раз)";
                $issues[] = "$serviceName: $msg (" . count($matches[0]) . " раз)";
            }
        }
    }

    // Проверка 4: Log::channel('audit')
    if (!str_contains($content, "Log::channel('audit')")) {
        $problems[] = "⚠️  Отсутствуют audit логи";
        $warnings[] = "$serviceName: No audit logs";
    } else {
        echo "  ✅ Audit логи есть\n";
    }

    // Проверка 5: correlation_id в логах
    $logCalls = substr_count($content, "Log::");
    $correlationInLogs = substr_count($content, "'correlation_id'");
    $correlationInLogs += substr_count($content, '"correlation_id"');

    if ($logCalls > 0 && $correlationInLogs === 0) {
        $problems[] = "⚠️  correlation_id не передаётся в логи ($logCalls вызовов Log)";
        $warnings[] = "$serviceName: No correlation_id in logs";
    }

    if (empty($problems)) {
        echo "  ✅ ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ\n";
    } else {
        echo "  ПРОБЛЕМЫ:\n";
        foreach ($problems as $problem) {
            echo "    $problem\n";
        }
    }

    echo "\n";
}

// Запустить проверки
foreach ($tier1Services as $service) {
    $fullPath = $servicesPath . '\\' . $service;
    checkService($fullPath, $service, $issues, $warnings);
}

// РЕЗЮМЕ
echo "\n════════════════════════════════════════════════════════════════\n";
echo "📊 ИТОГИ АУДИТА\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "❌ КРИТИЧНЫЕ ПРОБЛЕМЫ (" . count($issues) . "):\n";
foreach ($issues as $issue) {
    echo "  • $issue\n";
}

echo "\n⚠️  ПРЕДУПРЕЖДЕНИЯ (" . count($warnings) . "):\n";
if (is_array($warnings)) {
    foreach ($warnings as $key => $warning) {
        if (is_numeric($key)) {
            echo "  • $warning\n";
        } else {
            echo "  • $key: $warning\n";
        }
    }
}

echo "\n🎯 ПЛАН ДЕЙСТВИЙ:\n";
echo "  1. Добавить DB::transaction() в методы: debit, credit, hold, apply, init\n";
echo "  2. Заменить 'return false/null/[]' на 'throw new Exception()'\n";
echo "  3. Добавить correlation_id во все Log::channel('audit')\n";
echo "  4. Добавить FraudControlService::check() перед критичными операциями\n\n";

// Создать JSON отчёт
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_issues' => count($issues),
    'total_warnings' => count($warnings),
    'tier1_services_checked' => count($tier1Services),
    'issues' => $issues,
    'warnings' => $warnings,
];

file_put_contents(
    $basePath . '\\PHASE2_AUDIT_REPORT_' . date('Y-m-d_His') . '.json',
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "✅ Отчёт сохранён в PHASE2_AUDIT_REPORT_*.json\n";
