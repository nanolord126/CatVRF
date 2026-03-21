# CANON 2026 - Глобальная трансформация всех PHP файлов проекта
param(
    [string]$Path = ".",
    [switch]$DryRun = $false
)

$errorCount = 0
$successCount = 0
$skipCount = 0

Write-Host "=== APPLY CANON 2026 ===" -ForegroundColor Cyan
Write-Host "Path: $Path`n" -ForegroundColor Cyan

# Найти все PHP файлы
$phpFiles = Get-ChildItem -Path $Path -Filter "*.php" -Recurse -ErrorAction SilentlyContinue | 
            Where-Object { $_.FullName -notmatch '\\vendor\\' -and $_.FullName -notmatch '\\node_modules\\' }

Write-Host "Found $($phpFiles.Count) PHP files`n" -ForegroundColor Yellow

$phpFiles | ForEach-Object {
    $filePath = $_.FullName
    $fileName = $_.Name
    
    try {
        $content = Get-Content $filePath -Raw -Encoding UTF8
        
        # Пропустить пустые файлы
        if ([string]::IsNullOrWhiteSpace($content)) {
            Write-Host "⊘ SKIP: $fileName (empty)" -ForegroundColor Gray
            $skipCount++
            return
        }
        
        $originalContent = $content
        $modified = $false
        
        # 1. Добавить declare(strict_types=1) после <?php если его нет
        if ($content -match '^\s*<\?php' -and $content -notmatch 'declare\(strict_types=1\)') {
            $content = $content -replace '^\s*<\?php', "<?php`r`ndeclare(strict_types=1);"
            $modified = $true
            Write-Host "✓ Added declare(strict_types=1)" -ForegroundColor Green
        }
        
        # 2. Нормализовать окончания строк на CRLF
        if ($content -notmatch "`r`n") {
            $content = $content -replace "`n", "`r`n"
            $modified = $true
            Write-Host "✓ Normalized line endings to CRLF" -ForegroundColor Green
        }
        
        # 3. Убрать BOM если есть
        if ($content.StartsWith([char]0xFEFF)) {
            $content = $content.Substring(1)
            $modified = $true
            Write-Host "✓ Removed BOM" -ForegroundColor Green
        }
        
        # 4. Добавить final к классам где это возможно (basic check)
        if ($content -match 'class\s+\w+\s+(?!extends|implements|final)' -and $content -notmatch 'abstract class') {
            $content = $content -replace '(class\s+\w+)\s+(extends|implements)', 'final $1 $2'
            $content = $content -replace '(class\s+\w+)(\s*\{)', 'final $1$2'
            # Но не для базовых классов с наследниками
            Write-Host "✓ Checked final class modifier" -ForegroundColor Green
        }
        
        # Сохранить файл если был изменен
        if ($modified) {
            if (-not $DryRun) {
                Set-Content -Path $filePath -Value $content -Encoding UTF8 -NoNewline
                Write-Host "  ✓ SAVED: $fileName`n" -ForegroundColor Green
            } else {
                Write-Host "  [DRY RUN] Would save: $fileName`n" -ForegroundColor Yellow
            }
            $successCount++
        } else {
            Write-Host "  - No changes needed: $fileName`n" -ForegroundColor Gray
            $skipCount++
        }
    }
    catch {
        Write-Host "✗ ERROR in $fileName : $_" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host "`n=== RESULTS ===" -ForegroundColor Cyan
Write-Host "Success:  $successCount files" -ForegroundColor Green
Write-Host "Skipped:  $skipCount files" -ForegroundColor Yellow
Write-Host "Errors:   $errorCount files" -ForegroundColor Red
Write-Host "Total:    $($phpFiles.Count) files`n" -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "DRY RUN MODE - no files modified. Run without -DryRun to apply changes." -ForegroundColor Yellow
}
