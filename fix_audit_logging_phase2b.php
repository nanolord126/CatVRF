<?php declare(strict_types=1);

/**
 * PHASE 2B: Fix Audit Logging in Services
 * Добавляет Log::channel('audit') логирование в критичные методы
 */

$basePath = 'c:\\opt\\kotvrf\\CatVRF';

// Сервисы, требующие логирования
$servicesNeedingLogs = [
    'app\\Services\\PromoService.php' => [
        'applyPromo',
        'validatePromo',
        'cancelPromoUse',
    ],
    'app\\Services\\ReferralService.php' => [
        'generateReferralLink',
        'registerReferral',
        'checkQualification',
        'awardBonus',
    ],
];

echo "═══════════════════════════════════════════════════════\n";
echo "🔧 ФАЗА 2B: ДОБАВЛЕНИЕ AUDIT ЛОГИРОВАНИЯ\n";
echo "═══════════════════════════════════════════════════════\n\n";

foreach ($servicesNeedingLogs as $serviceFile => $methods) {
    $fullPath = $basePath . '\\' . str_replace('\\\\', '\\', $serviceFile);
    
    if (!file_exists($fullPath)) {
        echo "⏭️  ПРОПУЩЕН: $serviceFile\n";
        continue;
    }

    echo "📝 Обновляю: $serviceFile\n";
    $content = file_get_contents($fullPath);
    
    foreach ($methods as $method) {
        // Найти метод и проверить наличие логирования
        $pattern = "/public function $method\\s*\\([^)]*\\)[^{]*{/";
        
        if (preg_match($pattern, $content, $matches)) {
            // Проверить, есть ли уже Log::channel('audit')
            $methodStart = strpos($content, $matches[0]);
            $methodEnd = $methodStart + strlen($matches[0]);
            $nextBracePos = $methodEnd;
            $braceCount = 1;
            
            $tempPos = $methodEnd;
            while ($braceCount > 0 && $tempPos < strlen($content)) {
                if ($content[$tempPos] === '{') $braceCount++;
                if ($content[$tempPos] === '}') $braceCount--;
                $tempPos++;
            }
            
            $methodBody = substr($content, $methodStart, $tempPos - $methodStart);
            
            if (!str_contains($methodBody, "Log::channel('audit')")) {
                echo "  ✅ Добавляю логирование в $method()\n";
                
                // Добавить лог после открытия метода
                $logEntry = "        Log::channel('audit')->info('Method $method() called', [\n" .
                           "            'correlation_id' => \$correlationId ?? Str::uuid(),\n" .
                           "        ]);\n\n";
                
                $content = str_replace($matches[0], $matches[0] . "\n" . $logEntry, $content);
            } else {
                echo "  ℹ️  $method() уже имеет логирование\n";
            }
        }
    }
    
    file_put_contents($fullPath, $content);
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "✅ ЛОГИРОВАНИЕ ДОБАВЛЕНО\n";
echo "═══════════════════════════════════════════════════════\n";
