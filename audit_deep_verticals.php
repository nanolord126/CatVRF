<?php
$domainPath = __DIR__ . "/app/Domains";
$verticals = array_filter(glob($domainPath . "/*"), "is_dir");

$report = "# ГЛУБОКИЙ АУДИТ ВЕРТИКАЛЕЙ КАТАЛОГА (CANON 2026)\n";
$report .= "Дата: " . date("Y-m-d H:i:s") . "\n\n";

$totalIssues = 0;
$totalFilesScanned = 0;

$verticalScores = [];

foreach ($verticals as $verticalPath) {
    if (basename($verticalPath) === 'AI' || basename($verticalPath) === 'Core') continue;
    $verticalName = basename($verticalPath);
    
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($verticalPath));
    $phpFiles = [];
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === "php") {
            $phpFiles[] = $file->getPathname();
        }
    }
    
    $stats = [
        "models" => 0, "services" => 0, "controllers" => 0, "jobs" => 0, "livewire" => 0, "events" => 0,
        "strict_types_missing" => 0, "todos" => 0, "fraud_checks" => 0, "transactions" => 0,
        "tenant_scopes" => 0, "correlation_ids" => 0, "audit_logs" => 0, "return_nulls" => 0,
        "empty_files" => 0, "catch_missing_logs" => 0
    ];
    
    $issues = [];
    $critical_vulns = [];
    
    foreach ($phpFiles as $phpFile) {
        $totalFilesScanned++;
        $content = file_get_contents($phpFile);
        $fileName = basename($phpFile);
        
        // Normalize slashes
        $filePathNorm = str_replace(['\\', '/'], '/', $phpFile);
        $dirNorm = str_replace(['\\', '/'], '/', __DIR__);
        $relPath = str_replace($dirNorm . '/', '', $filePathNorm);
        
        if (trim($content) === "" || trim($content) === "<?php") {
            $stats["empty_files"]++;
            $issues[] = "- [!] Пустой файл-заглушка: {$relPath}";
            continue;
        }

        if (strpos($relPath, "/Models/") !== false) {
            $stats["models"]++;
            if (strpos($content, "tenant_id") === false && strpos($content, "TenantScope") === false && strpos($content, "BelongsToTenant") === false) {
                $critical_vulns[] = "- [КРИТИЧНО] Модель без Tenant-изоляции: {$relPath}";
            }
        }
        if (strpos($relPath, "/Services/") !== false) {
            $stats["services"]++;
            if (preg_match("/(update|save|create|delete)\s*\(/", $content) && strpos($content, "DB::transaction") === false) {
                $critical_vulns[] = "- [УЯЗВИМОСТЬ БД] Мутация без DB::transaction в сервисе: {$relPath}";
            }
            if (strpos($content, "function") !== false && strpos($content, "Log::channel('audit')") === false) {
                $issues[] = "- [Логи] Нет Audit-логов в сервисе: {$relPath}";
            }
        }
        if (strpos($relPath, "/Jobs/") !== false) $stats["jobs"]++;
        if (strpos($relPath, "/Events/") !== false) $stats["events"]++;
        if (strpos($relPath, "Livewire/") !== false) $stats["livewire"]++;
        
        // Canon Checks
        if (strpos($content, "declare(strict_types=1);") === false) {
            $stats["strict_types_missing"]++;
        }
        if (preg_match("/TODO|FIXME|\bdd\(|\bdump\(|\bvar_dump\(/i", $content)) {
            $stats["todos"]++;
            $issues[] = "- [БАГ] Забытый TODO/FIXME или dump() в `{$relPath}`";
        }
        if (preg_match("/return\s+null;/i", $content) && strpos($relPath, "/Models/") === false) {
            $stats["return_nulls"]++;
            $issues[] = "- [ОШИБКА АРХИТЕКТУРЫ] Возврат null (нужно исключение) в `{$relPath}`";
        }
        if (strpos($content, "FraudControlService::check") !== false) $stats["fraud_checks"]++;
        if (strpos($content, "DB::transaction") !== false) $stats["transactions"]++;
        if (strpos($content, "tenant_id") !== false || strpos($content, "tenant()") !== false) $stats["tenant_scopes"]++;
        if (strpos($content, "correlation_id") !== false || strpos($content, "correlationId") !== false) $stats["correlation_ids"]++;
        if (strpos($content, "Log::channel('audit')") !== false) $stats["audit_logs"]++;
        
        // Empty Catch check
        if (preg_match("/catch\s*\([^)]+\)\s*\{\s*\}/", $content)) {
            $stats["catch_missing_logs"]++;
            $critical_vulns[] = "- [УЯЗВИМОСТЬ] Пустой try/catch (скрытие ошибок) в: {$relPath}";
        }
    }
    
    // Check Filament Integration
    $filamentPath = __DIR__ . "/app/Filament/Tenant/Resources/{$verticalName}Resource.php";
    $hasFilament = file_exists($filamentPath);
    
    if (!$hasFilament && count($phpFiles) > 0) {
        $issues[] = "- [UI] Отсутствует интеграция с Filament B2B панелью (нет {$verticalName}Resource)";
    }

    $readiness = 0;
    if (count($phpFiles) === 0) {
        $readiness = 0;
        $critical_vulns[] = "- [ФЕЙК] Вертикаль создана, но файлов нет (0 файлов).";
    } else {
        if ($stats["models"] > 0) $readiness += 15; else $issues[] = "- [!] НЕТ МОДЕЛЕЙ";
        if ($stats["services"] > 0) $readiness += 25; else $issues[] = "- [!] НЕТ СЕРВИСОВ БИЗНЕС-ЛОГИКИ";
        if ($hasFilament) $readiness += 15;
        
        if ($stats["tenant_scopes"] > 0) $readiness += 10; else $critical_vulns[] = "- [КРИТИЧНО] Вообще нет изоляции Tenant (нет tenant_id в коде)!";
        if ($stats["transactions"] > 0) $readiness += 10; else $critical_vulns[] = "- [УЯЗВИМОСТЬ] Ни одной БД транзакции!";
        if ($stats["fraud_checks"] > 0) $readiness += 10; else $critical_vulns[] = "- [КРИТИЧНО] Нет интеграции с FraudMLService!";
        if ($stats["correlation_ids"] > 0) $readiness += 10; else $issues[] = "- [Лаг] Отсутствует сквозное логирование (correlation_id)!";
        if ($stats["audit_logs"] > 0) $readiness += 5; else $issues[] = "- [Лаг] Нет Audit-логов (Log::channel('audit'))!";
        
        // Penalties
        $readiness -= ($stats["empty_files"] * 5);
        $readiness -= ($stats["return_nulls"] * 2);
        $readiness -= ($stats["todos"] * 3);
        $readiness -= (count($critical_vulns) * 5);
        
        $readiness = max(0, min(100, $readiness));
    }
    
    $verticalScores[$verticalName] = $readiness;

    $report .= "## Вертикаль: {$verticalName} [Готовность: {$readiness}%]\n";
    $report .= "**Всего файлов:** " . count($phpFiles) . " (Models: {$stats['models']}, Services: {$stats['services']}, Jobs: {$stats['jobs']})\n";
    $report .= "**Метрики Canon:** Fraud Checks: {$stats['fraud_checks']}, Transactions: {$stats['transactions']}, Correlation IDs: {$stats['correlation_ids']}\n\n";
    
    if (count($critical_vulns) > 0) {
        $report .= "### 🚨 КРИТИЧЕСКИЕ УЯЗВИМОСТИ И ДЫРЫ:\n";
        $slicedVulns = array_slice($critical_vulns, 0, 10);
        foreach ($slicedVulns as $vuln) {
            $report .= $vuln . "\n";
        }
        if (count($critical_vulns) > 10) {
            $report .= "- ... и ещё " . (count($critical_vulns) - 10) . " критических проблем(ы).\n";
        }
        $report .= "\n";
    }
    
    if (count($issues) > 0) {
        $report .= "### ⚠️ БАГИ, ЛАГИ И DEBT:\n";
        $slicedIssues = array_slice($issues, 0, 10);
        foreach ($slicedIssues as $issue) {
            $report .= $issue . "\n";
        }
        if (count($issues) > 10) {
            $report .= "- ... и ещё " . (count($issues) - 10) . " багов/отклонений.\n";
        }
        $report .= "\n";
    } else {
        if (count($critical_vulns) === 0) {
            $report .= "*Инфраструктура выглядит чистой (Canon 2026 соблюден).* \n\n";
        }
    }
    
    $report .= "---\n\n";
    $totalIssues += count($issues) + count($critical_vulns);
}

$report .= "### ОБЩИЙ РЕЙТИНГ ГОТОВНОСТИ ВЕРТИКАЛЕЙ:\n";
arsort($verticalScores);
foreach ($verticalScores as $name => $score) {
    if ($score >= 85) $icon = "🟢";
    elseif ($score >= 50) $icon = "🟡";
    else $icon = "🔴";
    $report .= "- {$icon} {$name}: {$score}%\n";
}

$report .= "\n**Всего проанализировано файлов в доменах:** {$totalFilesScanned}\n";
$report .= "**Суммарно обнаружено отклонений/уязвимостей:** {$totalIssues}\n";

file_put_contents("AUDIT_DEEP_VERTICALS_2026.md", $report);
echo "Аудит завершен.\n";


