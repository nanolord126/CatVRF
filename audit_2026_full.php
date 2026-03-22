<?php
declare(strict_types=1);
// audit_2026_full.php — Комплексный детальный аудит проекта
// Запуск: php audit_2026_full.php
chdir(__DIR__);

$results = [];
$errors  = [];

function rfiles(string $dir, string $ext = 'php'): array {
    $out = [];
    if (!is_dir($dir)) return $out;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->getExtension() === $ext) $out[] = $f->getPathname();
    }
    return $out;
}

function content(string $path): string {
    return file_get_contents($path);
}

function shortPath(string $p): string {
    return str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $p);
}

$allApp   = rfiles('app');
$allTests = rfiles('tests');
$allMigs  = rfiles('database/migrations');
$domSvcs  = array_filter($allApp, fn($f) => str_contains($f, DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR) && str_contains($f, DIRECTORY_SEPARATOR . 'Domains' . DIRECTORY_SEPARATOR));
$domSvcs  = array_values($domSvcs);
$allCtrl  = array_filter($allApp, fn($f) => str_contains($f, 'Controllers'));
$allCtrl  = array_values($allCtrl);
$allModels= array_filter($allApp, fn($f) => str_contains($f, DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR));
$allModels= array_values($allModels);
$allJobs  = array_filter($allApp, fn($f) => str_ends_with($f, 'Job.php'));
$allPolicies = array_filter($allApp, fn($f) => str_contains($f, DIRECTORY_SEPARATOR . 'Policies' . DIRECTORY_SEPARATOR));

echo "=======================================================\n";
echo "  КОМПЛЕКСНЫЙ АУДИТ 2026 — " . date('Y-m-d H:i:s') . "\n";
echo "=======================================================\n\n";

echo "СТАТИСТИКА ПРОЕКТА\n";
echo str_repeat('-', 50) . "\n";
printf("%-35s %s\n", "PHP файлов в app/:",           count($allApp));
printf("%-35s %s\n", "Domain Services:",              count($domSvcs));
printf("%-35s %s\n", "Controllers:",                  count($allCtrl));
printf("%-35s %s\n", "Models:",                       count($allModels));
printf("%-35s %s\n", "Jobs:",                         count($allJobs));
printf("%-35s %s\n", "Policies:",                     count($allPolicies));
printf("%-35s %s\n", "Migrations:",                   count($allMigs));
printf("%-35s %s\n", "Test files:",                   count($allTests));
echo "\n";

// ═══════════════════════════════════════════════════
// 1. FRAUD CONTROL SERVICE
// ═══════════════════════════════════════════════════
echo "1. FRAUD CONTROL SERVICE\n";
echo str_repeat('-', 50) . "\n";

$wrongNs   = array_filter($allApp, fn($f) => str_contains(content($f), 'App\\Services\\Fraud\\FraudControlService'));
$noCheck   = array_filter($domSvcs, fn($f) => str_contains(content($f), 'fraudControlService') && !str_contains(content($f), '->check('));
$noFCS     = array_filter($domSvcs, fn($f) => !str_contains(content($f), 'FraudControlService'));
$dupUse    = array_filter($domSvcs, fn($f) => substr_count(content($f), 'use App\\Services\\FraudControlService;') > 1);
$brokenInj = array_filter($allApp,  fn($f) => str_contains(content($f), 'FraudControlService \\,'));
$ctrlNoFCS = array_filter($allCtrl, fn($f) => {
    $c = content($f);
    return preg_match('/(function (store|create|update|destroy|delete)\b|DB::transaction)/', $c)
        && !str_contains($c, 'FraudControlService');
});

printf("%-45s %s\n", "Неверный namespace (Fraud\\FCS):",       count($wrongNs)  === 0 ? '✅ 0' : '❌ ' . count($wrongNs));
printf("%-45s %s\n", "Сломанная инжекция (\\,):",              count($brokenInj)=== 0 ? '✅ 0' : '❌ ' . count($brokenInj));
printf("%-45s %s\n", "Дубли use import:",                      count($dupUse)   === 0 ? '✅ 0' : '❌ ' . count($dupUse));
printf("%-45s %s\n", "Сервисы без FCS (domain):",              count($noFCS)    === 0 ? '✅ 0' : '⚠️  ' . count($noFCS));
printf("%-45s %s\n", "Сервисы с FCS но без ->check():",        count($noCheck)  === 0 ? '✅ 0' : '❌ ' . count($noCheck));
printf("%-45s %s\n", "Контроллеры с мутациями без FCS:",       count($ctrlNoFCS)=== 0 ? '✅ 0' : '❌ ' . count($ctrlNoFCS));

foreach ($noCheck as $f)   $errors[] = "NO_CHECK: " . shortPath($f);
foreach ($ctrlNoFCS as $f) $errors[] = "CTRL_NO_FCS: " . shortPath($f);
foreach ($noFCS as $f)     { $n = basename($f); if (!preg_match('/(Interface|Abstract|Base|Trait|Contract)/i', $n)) $errors[] = "SVC_NO_FCS: " . shortPath($f); }
echo "\n";

// ═══════════════════════════════════════════════════
// 2. LOGGING
// ═══════════════════════════════════════════════════
echo "2. ЛОГИРОВАНИЕ (audit channel)\n";
echo str_repeat('-', 50) . "\n";

$svcNoLog  = array_filter($domSvcs, fn($f) => !str_contains(content($f), "Log::channel('audit')") && !str_contains(content($f), 'Log::info'));
$ctrlNoLog = array_filter($allCtrl, fn($f) => {
    $c = content($f);
    return preg_match('/(function (store|create|update|destroy|delete)\b|DB::transaction)/', $c)
        && !str_contains($c, "Log::channel") && !str_contains($c, 'Log::info');
});
$jobsNoLog = array_filter($allJobs, fn($f) => !str_contains(content($f), "Log::channel") && !str_contains(content($f), 'Log::info'));

printf("%-45s %s\n", "Domain сервисы без audit-лога:",         count($svcNoLog)  === 0 ? '✅ 0' : '⚠️  ' . count($svcNoLog));
printf("%-45s %s\n", "Контроллеры (мутации) без лога:",        count($ctrlNoLog) === 0 ? '✅ 0' : '❌ ' . count($ctrlNoLog));
printf("%-45s %s\n", "Jobs без лога:",                         count($jobsNoLog) === 0 ? '✅ 0' : '❌ ' . count($jobsNoLog));

foreach ($svcNoLog  as $f) $errors[] = "SVC_NO_LOG: " . shortPath($f);
foreach ($ctrlNoLog as $f) $errors[] = "CTRL_NO_LOG: " . shortPath($f);
foreach ($jobsNoLog as $f) $errors[] = "JOB_NO_LOG: " . shortPath($f);
echo "\n";

// ═══════════════════════════════════════════════════
// 3. DB::TRANSACTION
// ═══════════════════════════════════════════════════
echo "3. DB::TRANSACTION\n";
echo str_repeat('-', 50) . "\n";

$svcNoTx = array_filter($domSvcs, fn($f) => {
    $c = content($f);
    return preg_match('/(::create\(|->save\(|->update\(|->delete\()/', $c) && !str_contains($c, 'DB::transaction');
});

printf("%-45s %s\n", "Сервисы с мутациями без DB::transaction:", count($svcNoTx) === 0 ? '✅ 0' : '⚠️  ' . count($svcNoTx));
if (count($svcNoTx) > 0) {
    foreach (array_slice($svcNoTx, 0, 10) as $f) echo "    ⚠ " . shortPath($f) . "\n";
    if (count($svcNoTx) > 10) echo "    ... и ещё " . (count($svcNoTx) - 10) . "\n";
}
echo "\n";

// ═══════════════════════════════════════════════════
// 4. CODE QUALITY
// ═══════════════════════════════════════════════════
echo "4. КАЧЕСТВО КОДА\n";
echo str_repeat('-', 50) . "\n";

$todo      = array_filter($allApp, fn($f) => preg_match('/\b(TODO|FIXME|STUB)\b/', content($f)));
$retNull   = array_filter($allApp, fn($f) => {
    return preg_match('/return null;/', content($f))
        && preg_match('/(Services|Controllers|Jobs)/', $f)
        && !preg_match('/(Test|Seeder|Factory|Migration)/', $f);
});
$noStrict  = array_filter($allApp, fn($f) => !str_contains(content($f), 'declare(strict_types=1)'));
$noFinal   = array_filter($domSvcs, fn($f) => !preg_match('/^(final\s+class|abstract\s+class|interface\s+)/m', content($f)));
$noCorrelation = array_filter($domSvcs, fn($f) => !str_contains(content($f), 'correlationId') && !str_contains(content($f), 'correlation_id'));

printf("%-45s %s\n", "TODO/FIXME/STUB:",                        count($todo)          === 0 ? '✅ 0' : '❌ ' . count($todo));
printf("%-45s %s\n", "return null в сервисах/контроллерах:",    count($retNull)       === 0 ? '✅ 0' : '❌ ' . count($retNull));
printf("%-45s %s\n", "Файлы без declare(strict_types=1):",      count($noStrict)      === 0 ? '✅ 0' : '⚠️  ' . count($noStrict));
printf("%-45s %s\n", "Domain сервисы без final:",               count($noFinal)       === 0 ? '✅ 0' : '⚠️  ' . count($noFinal));
printf("%-45s %s\n", "Сервисы без correlation_id:",             count($noCorrelation) === 0 ? '✅ 0' : '⚠️  ' . count($noCorrelation));

foreach ($todo    as $f) $errors[] = "TODO: " . shortPath($f);
foreach ($retNull as $f) $errors[] = "RETURN_NULL: " . shortPath($f);
echo "\n";

// ═══════════════════════════════════════════════════
// 5. MODELS
// ═══════════════════════════════════════════════════
echo "5. МОДЕЛИ\n";
echo str_repeat('-', 50) . "\n";

$noTenantScope = array_filter($allModels, fn($f) => {
    $c = content($f);
    return !preg_match('/(abstract|interface)/i', $c)
        && !str_contains($c, 'tenant_id')
        && !preg_match('/(User|Tenant|Role|Permission|Migration|Pivot)/i', basename($f));
});
$noFillable    = array_filter($allModels, fn($f) => !str_contains(content($f), '$fillable') && !preg_match('/(abstract|interface|Pivot)/i', content($f)));
$noUUID        = array_filter($allModels, fn($f) => {
    $c = content($f);
    return !str_contains($c, 'uuid') && !preg_match('/(abstract|interface|Pivot|User|Migration)/i', $c);
});
$noCasts       = array_filter($allModels, fn($f) => !str_contains(content($f), '$casts') && !preg_match('/(abstract|interface|Pivot)/i', content($f)));

printf("%-45s %s\n", "Модели без tenant_id:",                   count($noTenantScope) < 5 ? '✅ ' . count($noTenantScope) : '⚠️  ' . count($noTenantScope));
printf("%-45s %s\n", "Модели без \$fillable:",                  count($noFillable)    === 0 ? '✅ 0' : '⚠️  ' . count($noFillable));
printf("%-45s %s\n", "Модели без \$casts:",                     count($noCasts)       === 0 ? '✅ 0' : '⚠️  ' . count($noCasts));
printf("%-45s %s\n", "Модели без uuid:",                        count($noUUID)        < 5 ? '✅ ' . count($noUUID) : '⚠️  ' . count($noUUID));
echo "\n";

// ═══════════════════════════════════════════════════
// 6. MIGRATIONS
// ═══════════════════════════════════════════════════
echo "6. МИГРАЦИИ\n";
echo str_repeat('-', 50) . "\n";

$migNoIdempotent = array_filter($allMigs, fn($f) => !preg_match('/(hasTable|hasColumn|Schema::has)/i', content($f)));
$migNoDown       = array_filter($allMigs, fn($f) => !preg_match('/public function down\(\)/i', content($f)));
$migNoComment    = array_filter($allMigs, fn($f) => !str_contains(content($f), '->comment('));

printf("%-45s %s\n", "Миграции без idempotent guard:",          count($migNoIdempotent) < 3 ? '✅ ' . count($migNoIdempotent) : '⚠️  ' . count($migNoIdempotent));
printf("%-45s %s\n", "Миграции без down():",                    count($migNoDown)       === 0 ? '✅ 0' : '⚠️  ' . count($migNoDown));
printf("%-45s %s\n", "Миграции без ->comment():",               count($migNoComment)    < 5 ? '✅ ' . count($migNoComment) : '⚠️  ' . count($migNoComment));
echo "\n";

// ═══════════════════════════════════════════════════
// 7. TESTS
// ═══════════════════════════════════════════════════
echo "7. ТЕСТЫ\n";
echo str_repeat('-', 50) . "\n";

$testFiles       = rfiles('tests');
$svcTests        = array_filter($testFiles, fn($f) => preg_match('/(Service|Controller)Test\.php$/', $f));
$testsNoFCS      = array_filter($svcTests,  fn($f) => !str_contains(content($f), 'FraudControlService'));
$testsNoAssertDB = array_filter($svcTests,  fn($f) => !str_contains(content($f), 'assertDatabaseHas'));
$testsNoCorr     = array_filter($svcTests,  fn($f) => !str_contains(content($f), 'correlation_id') && !str_contains(content($f), 'correlationId'));

printf("%-45s %s\n", "Всего тестов:",                           count($testFiles));
printf("%-45s %s\n", "Service/Controller тестов:",              count($svcTests));
printf("%-45s %s\n", "Тесты без FCS mock:",                     count($testsNoFCS)      === 0 ? '✅ 0' : '⚠️  ' . count($testsNoFCS));
printf("%-45s %s\n", "Тесты без assertDatabaseHas:",            count($testsNoAssertDB) === 0 ? '✅ 0' : '⚠️  ' . count($testsNoAssertDB));
printf("%-45s %s\n", "Тесты без correlation_id:",               count($testsNoCorr)     === 0 ? '✅ 0' : '⚠️  ' . count($testsNoCorr));

if (count($testsNoFCS) > 0) {
    echo "  Тесты без FCS mock:\n";
    foreach ($testsNoFCS as $f) echo "    ⚠ " . basename($f) . "\n";
}
echo "\n";

// ═══════════════════════════════════════════════════
// 8. SECURITY
// ═══════════════════════════════════════════════════
echo "8. БЕЗОПАСНОСТЬ\n";
echo str_repeat('-', 50) . "\n";

$noRateLimit   = array_filter($allCtrl, fn($f) => {
    $c = content($f);
    return preg_match('/(function (store|init|create)\b)/', $c) && !preg_match('/(RateLimiter|throttle|rate_limit)/i', $c)
        && !preg_match('/(Internal|Webhook)/i', $f);
});
$noSanctum = array_filter($allCtrl, fn($f) => {
    $c = content($f);
    return preg_match('/function (store|init|create|update|delete)\b/', $c)
        && !preg_match('/(auth:sanctum|auth:api|middleware)/i', $c)
        && !preg_match('/(Internal|Webhook|Base)/i', $f);
});
$webhookNoSig = array_filter($allCtrl, fn($f) => {
    $c = content($f);
    return preg_match('/webhook/i', $f) && !preg_match('/(signature|hmac|hash_hmac|verify)/i', $c);
});

printf("%-45s %s\n", "Контроллеры без RateLimit:",              count($noRateLimit)  === 0 ? '✅ 0' : '⚠️  ' . count($noRateLimit));
printf("%-45s %s\n", "Webhook без проверки подписи:",           count($webhookNoSig) === 0 ? '✅ 0' : '❌ ' . count($webhookNoSig));

foreach ($webhookNoSig as $f) $errors[] = "WEBHOOK_NO_SIG: " . shortPath($f);
echo "\n";

// ═══════════════════════════════════════════════════
// 9. DOMAIN COMPLETENESS
// ═══════════════════════════════════════════════════
echo "9. ПОЛНОТА ДОМЕНОВ\n";
echo str_repeat('-', 50) . "\n";

$domains = glob('app/Domains/*', GLOB_ONLYDIR);
echo "Домены (" . count($domains) . "):\n";
foreach ($domains as $d) {
    $name = basename($d);
    $hasSvc  = is_dir("$d/Services");
    $hasModel= is_dir("$d/Models");
    $hasEvents = is_dir("$d/Events");
    $svcCount = $hasSvc ? count(glob("$d/Services/*.php")) : 0;
    $modCount = $hasModel ? count(glob("$d/Models/*.php")) : 0;
    $icon = ($hasSvc && $hasModel) ? '✅' : '⚠️ ';
    printf("  %s %-28s svc=%-3s mod=%-3s events=%s\n", $icon, $name, $svcCount, $modCount, $hasEvents ? 'yes' : 'no');
}
echo "\n";

// ═══════════════════════════════════════════════════
// 10. КРИТИЧЕСКИЕ ОШИБКИ
// ═══════════════════════════════════════════════════
echo "10. КРИТИЧЕСКИЕ ОШИБКИ\n";
echo str_repeat('-', 50) . "\n";

$critical = array_filter($errors, fn($e) => preg_match('/^(NO_CHECK|CTRL_NO_FCS|WEBHOOK_NO_SIG|RETURN_NULL|TODO)/', $e));
$warnings = array_filter($errors, fn($e) => !preg_match('/^(NO_CHECK|CTRL_NO_FCS|WEBHOOK_NO_SIG|RETURN_NULL|TODO)/', $e));

if (empty($critical)) {
    echo "  ✅ Критических ошибок нет\n";
} else {
    foreach ($critical as $e) echo "  ❌ $e\n";
}

echo "\nПРЕДУПРЕЖДЕНИЯ (" . count($warnings) . "):\n";
if (empty($warnings)) {
    echo "  ✅ Предупреждений нет\n";
} else {
    foreach (array_slice($warnings, 0, 20) as $e) echo "  ⚠  $e\n";
    if (count($warnings) > 20) echo "  ... и ещё " . (count($warnings)-20) . " предупреждений\n";
}

echo "\n=======================================================\n";
echo "ИТОГ: Критических=" . count($critical) . " Предупреждений=" . count($warnings) . "\n";
if (empty($critical) && empty($warnings)) {
    echo "🎉 ВСЕ ПРОВЕРКИ ПРОШЛИ!\n";
} elseif (empty($critical)) {
    echo "✅ Критических ошибок нет. Требует внимания: " . count($warnings) . "\n";
} else {
    echo "❌ Требует исправлений: " . count($critical) . " критических\n";
}
echo "=======================================================\n";
