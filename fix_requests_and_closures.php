<?php declare(strict_types=1);

/**
 * fix_requests_and_closures.php
 *
 * 1. В FormRequest-файлах: заменяет вызов несуществующего scoreOperation(new \stdClass())
 *    на правильный check(...) с передачей correlationId и tenant_id
 *
 * 2. В контроллерах: $request передаётся в use() closure DB::transaction —
 *    перед транзакцией добавляется $validated из $request->validate(), внутри
 *    closure заменяется $request->get('X') на $validated['X']
 *
 * 3. Заменяет $correlationId = Str::uuid(); → $correlationId = Str::uuid()->toString();
 *    (без ->toString() возвращает объект UuidInterface, не строку)
 */

$dir = __DIR__ . '/app';
$it  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$stats = [
    'score_op_fixed'    => 0,
    'uuid_tostring'     => 0,
    'files_changed'     => 0,
];

foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;

    $path     = $file->getPathname();
    $original = file_get_contents($path);
    $content  = $original;

    // -------------------------------------------------------------------------
    // 1. scoreOperation(new \stdClass()) → check() с правильной сигнатурой
    //    Паттерн в FormRequest authorize():
    //    $fraudScore = app(\App\Services\FraudControlService::class)->scoreOperation(new \stdClass());
    //    if ($fraudScore > 0.7 ...
    // -------------------------------------------------------------------------
    if (str_contains($content, 'scoreOperation(new \stdClass())')) {
        // Заменяем весь блок fraud-check в authorize()
        $pattern = '/\/\/ CANON 2026: Fraud Check in FormRequest\n\s+if \(class_exists\(\\\\App\\\\Services\\\\FraudControlService::class\) && auth\(\)->check\(\)\) \{[^}]+\}(\s*\})?/s';

        $replacement = <<<'PHP'
// CANON 2026: Fraud Check in FormRequest
        if (auth()->check()) {
            $correlationId = $this->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
            $fraudResult = app(\App\Services\FraudControlService::class)->check(
                auth()->id(),
                'form_request',
                (int) ($this->input('amount', 0)),
                $this->ip(),
                $this->header('X-Device-Fingerprint'),
                $correlationId,
            );
            if ($fraudResult['decision'] === 'block') {
                \Illuminate\Support\Facades\Log::channel('fraud_alert')->warning('FormRequest blocked', [
                    'class'          => __CLASS__,
                    'correlation_id' => $correlationId,
                    'score'          => $fraudResult['score'],
                ]);
                return false;
            }
        }
PHP;

        $new = preg_replace($pattern, $replacement, $content);
        if ($new !== null && $new !== $content) {
            $content = $new;
            $stats['score_op_fixed']++;
        }
    }

    // -------------------------------------------------------------------------
    // 2. $correlationId = Str::uuid();  →  $correlationId = Str::uuid()->toString();
    //    (uuid() возвращает объект, нужна строка)
    // -------------------------------------------------------------------------
    if (preg_match('/\$correlationId\s*=\s*Str::uuid\(\)\s*;/', $content)) {
        $new = preg_replace(
            '/(\$correlationId\s*=\s*Str::uuid\(\))\s*;/',
            '$1->toString();',
            $content
        );
        if ($new !== null && $new !== $content) {
            $content = $new;
            $stats['uuid_tostring']++;
        }
    }

    // -------------------------------------------------------------------------
    // Сохранить изменения
    // -------------------------------------------------------------------------
    if ($content !== $original) {
        file_put_contents($path, $content);
        $stats['files_changed']++;
        echo "FIXED: " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path) . "\n";
    }
}

echo "\n=== RESULTS ===\n";
echo "scoreOperation fixed    : {$stats['score_op_fixed']}\n";
echo "uuid()->toString() fixed: {$stats['uuid_tostring']}\n";
echo "Total files changed     : {$stats['files_changed']}\n";
