<?php declare(strict_types=1);

/**
 * fix_fraud_stubs.php
 * Заменяет пустые if (class_exists('\App\Services\FraudControlService')) { }
 * на реальный fraud-check, если FraudControlService уже инжектирован.
 * Если не инжектирован — добавляет конструктор и инъекцию + реальный check.
 */

$dir      = __DIR__ . '/app';
$it       = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$fixed    = 0;
$noInject = 0;
$changed  = [];

// Точная строка стаба (оба варианта кавычек)
$stubVariants = [
    "if (class_exists('\\App\\Services\\FraudControlService')) {\n        }",
    "if (class_exists('\\App\\Services\\FraudControlService')) {\n        }\n",
    "if (class_exists(\\App\\Services\\FraudControlService::class)) {\n        }",
];

foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;

    $path     = $file->getPathname();
    $original = file_get_contents($path);
    $content  = $original;

    // Нормализованный поиск: убираем вариации пробелов между { и }
    // Паттерн: if (class_exists(...FraudControl...)) {<пробелы/переводы>}
    $pattern = "/if\s*\(\s*class_exists\s*\(\s*'\\\\?App\\\\Services\\\\FraudControlService'\s*\)\s*\)\s*\{\s*\}/";

    if (!preg_match($pattern, $content)) {
        continue;
    }

    $hasFraudInjected = str_contains($content, 'private readonly FraudControlService $fraudControlService')
        || str_contains($content, 'private FraudControlService $fraudControlService');

    $hasLogImport = str_contains($content, "use Illuminate\\Support\\Facades\\Log;");

    // Убедиться что correlationId будет определён к моменту fraud-check
    // Ищем шаблон: перед стабом уже есть $correlationId = Str::uuid
    // Если нет — добавляем его перед заменой

    if ($hasFraudInjected) {
        // Заменяем пустой стаб на реальный fraud-check
        $replacement = <<<'REPLACEMENT'
$fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }
REPLACEMENT;

        $new = preg_replace($pattern, $replacement, $content);

        // Добавить Log import если его нет
        if (!$hasLogImport && $new !== null) {
            $new = preg_replace(
                "/(use Illuminate\\\\Support\\\\Str;)/",
                "use Illuminate\\Support\\Facades\\Log;\n$1",
                $new,
                1
            );
        }

        if ($new !== null && $new !== $content) {
            $content = $new;
            $fixed++;
            echo "FIXED (has injection): " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path) . "\n";
        }
    } else {
        // Инъекции нет — добавляем FraudControlService в конструктор и import
        // Сначала добавляем use-import если нет
        if (!str_contains($content, 'use App\\Services\\FraudControlService;')) {
            // Вставить после последнего use-импорта
            $content = preg_replace(
                '/(use [A-Za-z\\\\]+;\n)(?!use )/',
                "$1use App\\Services\\FraudControlService;\n",
                $content,
                1
            );
        }

        if (!$hasLogImport) {
            $content = preg_replace(
                "/(use Illuminate\\\\Support\\\\Str;)/",
                "use Illuminate\\Support\\Facades\\Log;\n$1",
                $content,
                1
            );
        }

        // Добавляем в конструктор если он есть
        if (preg_match('/public function __construct\s*\(([^)]*)\)/s', $content, $m)) {
            $existing = $m[1];
            // Добавляем FraudControlService если его нет
            if (!str_contains($existing, 'FraudControlService')) {
                $newParam = trim($existing) !== ''
                    ? $existing . ",\n        private readonly FraudControlService \$fraudControlService,"
                    : "\n        private readonly FraudControlService \$fraudControlService,\n    ";
                $content = str_replace($m[0], "public function __construct($newParam)", $content);
            }
        } elseif (str_contains($content, 'final class ') || str_contains($content, 'class ')) {
            // Конструктора нет — добавляем новый после открывающей скобки класса
            $content = preg_replace(
                '/((?:final\s+)?class\s+\w+[^{]*\{)/',
                "$1\n    public function __construct(\n        private readonly FraudControlService \$fraudControlService,\n    ) {}\n",
                $content,
                1
            );
        }

        // Заменяем стаб
        $replacement = <<<'REPLACEMENT'
$fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }
REPLACEMENT;

        $new = preg_replace($pattern, $replacement, $content);
        if ($new !== null && $new !== $original) {
            $content = $new;
            $noInject++;
            echo "FIXED (injected new): " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path) . "\n";
        }
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changed[] = $path;
    }
}

echo "\n=== RESULTS ===\n";
echo "Stubs fixed (had injection): $fixed\n";
echo "Stubs fixed (added injection): $noInject\n";
echo "Total files changed: " . count($changed) . "\n";
