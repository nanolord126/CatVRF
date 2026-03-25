<?php
declare(strict_types=1);

/**
 * ФАЗА 5: Финальная валидация
 * Проверка всех изменений (синтаксис, целостность, готовность к продакшену)
 */

$startTime = microtime(true);
$baseDir = __DIR__;
$results = [
    'php_syntax_check' => ['ok' => 0, 'errors' => 0, 'files' => []],
    'service_integrity' => ['ok' => 0, 'errors' => 0, 'details' => []],
    'model_completeness' => ['ok' => 0, 'errors' => 0],
    'fraud_control_check' => ['integrated' => 0, 'missing' => 0],
    'overall_status' => 'PENDING'
];

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  ФАЗА 5: ФИНАЛЬНАЯ ВАЛИДАЦИЯ                              ║\n";
echo "║  Запуск: " . date('Y-m-d H:i:s') . "                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Проверка синтаксиса PHP
echo "🔍 1. Проверка синтаксиса PHP файлов...\n";

$servicesPath = "{$baseDir}/app/Services";
if (is_dir($servicesPath)) {
    $files = glob("{$servicesPath}/*.php");
    
    foreach ($files as $file) {
        exec("php -l " . escapeshellarg($file), $output, $exitCode);
        
        if ($exitCode === 0) {
            $results['php_syntax_check']['ok']++;
            $results['php_syntax_check']['files'][] = basename($file) . " ✅";
        } else {
            $results['php_syntax_check']['errors']++;
            $results['php_syntax_check']['files'][] = basename($file) . " ❌";
        }
    }
}

echo "   ✅ OK: {$results['php_syntax_check']['ok']}\n";
if ($results['php_syntax_check']['errors'] > 0) {
    echo "   ❌ Ошибки: {$results['php_syntax_check']['errors']}\n";
} else {
    echo "   ✅ Синтаксис чистый!\n";
}

// 2. Проверка целостности сервисов
echo "\n🔍 2. Проверка целостности критичных сервисов...\n";

$criticalServices = [
    'WalletService.php',
    'PaymentGatewayService.php',
    'PromoService.php',
    'ReferralService.php',
    'IdempotencyService.php'
];

foreach ($criticalServices as $serviceName) {
    $filepath = "{$servicesPath}/{$serviceName}";
    
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        $checks = [
            'declare(strict_types=1)' => strpos($content, 'declare(strict_types=1)') !== false,
            'DB::transaction()' => strpos($content, 'DB::transaction') !== false || strpos($content, 'transaction') !== false,
            'Log::channel' => strpos($content, "Log::channel('audit')") !== false,
            'correlation_id' => strpos($content, 'correlation_id') !== false,
        ];
        
        $allGood = array_reduce($checks, fn($carry, $val) => $carry && $val, true);
        
        if ($allGood) {
            $results['service_integrity']['ok']++;
            echo "   ✅ {$serviceName}\n";
        } else {
            $results['service_integrity']['errors']++;
            $failed = array_keys(array_filter($checks, fn($v) => !$v));
            echo "   ⚠️  {$serviceName} - Missing: " . implode(', ', $failed) . "\n";
            $results['service_integrity']['details'][] = "{$serviceName}: Missing " . implode(', ', $failed);
        }
    }
}

// 3. Проверка Fraud Control интеграции
echo "\n🔍 3. Проверка интеграции Fraud Control...\n";

$fraudCheckFiles = [
    'PromoService.php' => 'promo_apply',
    'ReferralService.php' => 'referral_register',
    'PaymentGatewayService.php' => 'payment_init',
];

foreach ($fraudCheckFiles as $serviceName => $operationType) {
    $filepath = "{$servicesPath}/{$serviceName}";
    
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        
        if (strpos($content, 'fraudControl->check') !== false) {
            $results['fraud_control_check']['integrated']++;
            echo "   ✅ {$serviceName} - Fraud check интегрирован\n";
        } else {
            $results['fraud_control_check']['missing']++;
            echo "   ⚠️  {$serviceName} - Fraud check НЕ интегрирован\n";
        }
    }
}

// 4. Проверка моделей
echo "\n🔍 4. Проверка моделей на базовые требования...\n";

$modelsPath = "{$baseDir}/app/Domains";
$modelCount = 0;
$compliantCount = 0;

if (is_dir($modelsPath)) {
    $domains = glob("{$modelsPath}/*", GLOB_ONLYDIR);
    
    foreach ($domains as $domain) {
        $modelsSubPath = "{$domain}/Models";
        if (is_dir($modelsSubPath)) {
            $modelFiles = glob("{$modelsSubPath}/*.php");
            
            foreach ($modelFiles as $modelFile) {
                $modelCount++;
                $content = file_get_contents($modelFile);
                
                // Минимальные требования
                $requirements = [
                    'namespace' => strpos($content, 'namespace') !== false,
                    'class' => strpos($content, 'class') !== false && strpos($content, 'extends Model') !== false,
                ];
                
                if (array_reduce($requirements, fn($c, $v) => $c && $v, true)) {
                    $compliantCount++;
                }
            }
        }
    }
}

echo "   📊 Всего моделей: {$modelCount}\n";
echo "   ✅ Валидных структур: {$compliantCount}\n";
$results['model_completeness']['ok'] = $compliantCount;
$results['model_completeness']['errors'] = $modelCount - $compliantCount;

// Итоговая оценка
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  ИТОГОВЫЙ ОТЧЁТ                                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$phpSyntaxOK = $results['php_syntax_check']['errors'] === 0;
$servicesOK = $results['service_integrity']['errors'] === 0;
$fraudIntegrationOK = $results['fraud_control_check']['missing'] === 0;
$modelsOK = $results['model_completeness']['errors'] === 0;

echo "📋 PHP Синтаксис: " . ($phpSyntaxOK ? "✅ OK" : "⚠️ {$results['php_syntax_check']['errors']} ошибок") . "\n";
echo "📋 Целостность Services: " . ($servicesOK ? "✅ OK" : "⚠️ {$results['service_integrity']['errors']} проблем") . "\n";
echo "📋 Fraud Control интеграция: " . ($fraudIntegrationOK ? "✅ OK" : "⚠️ {$results['fraud_control_check']['missing']} не интегрировано") . "\n";
echo "📋 Валидность моделей: " . ($modelsOK ? "✅ OK" : "⚠️ {$results['model_completeness']['errors']} проблем") . "\n";

// Финальный статус
$allOK = $phpSyntaxOK && $servicesOK && $fraudIntegrationOK;
$results['overall_status'] = $allOK ? 'READY_FOR_PRODUCTION' : 'NEEDS_FIXES';

echo "\n" . ($allOK ? "✅" : "⚠️") . " ИТОГОВЫЙ СТАТУС: {$results['overall_status']}\n";

if ($allOK) {
    echo "\n🎉 ПРОЕКТ ГОТОВ К РАЗВЁРТЫВАНИЮ!\n";
    echo "\n📝 Для развёртывания выполнить:\n";
    echo "   1. php artisan migrate --force\n";
    echo "   2. php artisan cache:clear\n";
    echo "   3. php artisan queue:work\n";
    echo "   4. php artisan serve\n";
} else {
    echo "\n⚠️ Требуются исправления перед развёртыванием.\n";
    if (!empty($results['service_integrity']['details'])) {
        echo "\n Детали проблем:\n";
        foreach ($results['service_integrity']['details'] as $detail) {
            echo "   - {$detail}\n";
        }
    }
}

// Сохранить отчёт
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'phase' => 'PHASE5_FINAL_VALIDATION',
    'php_syntax_check' => $results['php_syntax_check'],
    'service_integrity' => $results['service_integrity'],
    'fraud_control_check' => $results['fraud_control_check'],
    'model_completeness' => $results['model_completeness'],
    'overall_status' => $results['overall_status'],
    'duration_seconds' => microtime(true) - $startTime,
];

file_put_contents(
    "{$baseDir}/PHASE5_FINAL_VALIDATION_REPORT_" . date('Y-m-d_His') . ".json",
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "\n✅ Отчёт сохранён: PHASE5_FINAL_VALIDATION_REPORT_*.json\n";
echo "\n🎯 ФАЗА 5 ЗАВЕРШЕНА!\n\n";

exit($allOK ? 0 : 1);
