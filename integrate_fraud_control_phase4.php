<?php declare(strict_types=1);

/**
 * PHASE 4: Fraud Control Service Integration
 * Интегрирует FraudControlService::check() в критичные методы
 * 
 * Целевые сервисы:
 * 1. PromoService::applyPromo()
 * 2. ReferralService::registerReferral()
 * 3. PaymentGatewayService::initPayment()
 * 4. WalletService::debit()
 */

$basePath = 'c:\\opt\\kotvrf\\CatVRF';

$integrations = [
    [
        'file' => 'app\\Services\\PromoService.php',
        'method' => 'applyPromo',
        'check' => [
            'operation' => 'promo_apply',
            'user_id' => '$userId',
            'discount' => '$discount',
        ],
        'insertAfter' => 'return DB::transaction(function',
    ],
    [
        'file' => 'app\\Services\\ReferralService.php',
        'method' => 'registerReferral',
        'check' => [
            'operation' => 'referral_register',
            'referrer_id' => '$referrerId',
            'referee_id' => '$newUserId',
        ],
        'insertAfter' => 'return DB::transaction(function',
    ],
    [
        'file' => 'app\\Services\\PaymentGatewayService.php',
        'method' => 'initPayment',
        'check' => [
            'operation' => 'payment_init',
            'user_id' => '$paymentData[\'user_id\']',
            'amount' => '$paymentData[\'amount\']',
        ],
        'insertAfter' => 'return DB::transaction(function',
    ],
];

echo "═════════════════════════════════════════════════════════════\n";
echo "🔐 ФАЗА 4: ИНТЕГРАЦИЯ FRAUD CONTROL SERVICE\n";
echo "═════════════════════════════════════════════════════════════\n\n";

foreach ($integrations as $integration) {
    $fullPath = $basePath . '\\' . str_replace('\\\\', '\\', $integration['file']);
    
    if (!file_exists($fullPath)) {
        echo "⏭️  ПРОПУЩЕН: {$integration['file']}\n";
        continue;
    }

    echo "📝 Обновляю: {$integration['file']}::{$integration['method']}()\n";
    
    $content = file_get_contents($fullPath);
    
    // Проверить наличие FraudControlService в конструкторе
    if (!str_contains($content, 'FraudControlService')) {
        echo "  ⚠️  Добавляю FraudControlService в конструктор...\n";
        
        // Добавить в конструктор
        $content = str_replace(
            'public function __construct(',
            "public function __construct(\n        private readonly FraudControlService \$fraudControl,\n",
            $content
        );
    }
    
    // Добавить check вызов в метод
    $fraudCheck = "        \$this->fraudControl->check([\n";
    foreach ($integration['check'] as $key => $value) {
        $fraudCheck .= "            '$key' => $value,\n";
    }
    $fraudCheck .= "            'correlation_id' => \$correlationId,\n";
    $fraudCheck .= "        ]);\n\n";
    
    // Найти точку вставки и добавить проверку
    if (str_contains($content, "public function {$integration['method']}(")) {
        $pattern = "/public function {$integration['method']}\\([^)]*\\)[^{]*{/";
        
        if (preg_match($pattern, $content, $matches)) {
            $insertPos = strpos($content, $matches[0]) + strlen($matches[0]);
            
            // Проверить, нет ли уже проверки
            $nextLines = substr($content, $insertPos, 500);
            if (!str_contains($nextLines, 'fraudControl->check')) {
                $before = substr($content, 0, $insertPos);
                $after = substr($content, $insertPos);
                $content = $before . "\n" . $fraudCheck . $after;
                
                echo "  ✅ Fraud check добавлен\n";
            } else {
                echo "  ℹ️  Fraud check уже есть\n";
            }
        }
    }
    
    file_put_contents($fullPath, $content);
    echo "\n";
}

echo "\n═════════════════════════════════════════════════════════════\n";
echo "✅ ИНТЕГРАЦИЯ ЗАВЕРШЕНА\n";
echo "═════════════════════════════════════════════════════════════\n\n";

echo "🎯 ПРОВЕРЬТЕ:\n";
echo "  1. FraudControlService инъектирован в конструкторы\n";
echo "  2. Вызовы $fraudControl->check() добавлены в критичные методы\n";
echo "  3. correlation_id передаётся во все методы\n\n";

echo "🚀 СЛЕДУЮЩИЙ ШАГ:\n";
echo "  php artisan tinker  # Проверить зависимости\n";
