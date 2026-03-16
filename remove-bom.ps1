# PowerShell скрипт для удаления BOM из PHP файлов
# Использование: .\remove-bom.ps1

$ErrorActionPreference = 'SilentlyContinue'
$startTime = Get-Date

Write-Host "`n🔍 УДАЛЕНИЕ BOM ИЗ PHP ФАЙЛОВ`n" -ForegroundColor Cyan
Write-Host "Директории: app, database, config" -ForegroundColor White

$dirs = @('app', 'database', 'config')
$totalFixed = 0
$totalChecked = 0

foreach ($dir in $dirs) {
    if (!(Test-Path $dir)) {
        Write-Host "⚠️  Директория не найдена: $dir" -ForegroundColor Yellow
        continue
    }
    
    $phpFiles = @(Get-ChildItem $dir -Filter '*.php' -Recurse -ErrorAction SilentlyContinue)
    
    if ($phpFiles.Count -eq 0) {
        Write-Host "⚠️  Нет PHP файлов в: $dir" -ForegroundColor Yellow
        continue
    }
    
    Write-Host "`n📁 Обработка: $dir" -ForegroundColor Cyan
    Write-Host "   Найдено файлов: $($phpFiles.Count)" -ForegroundColor Gray
    
    foreach ($file in $phpFiles) {
        $totalChecked++
        
        try {
            $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
            
            # Проверяем BOM (UTF-8 с BOM = 0xEF 0xBB 0xBF)
            if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
                # Удаляем первые 3 байта
                $cleanedBytes = $bytes[3..($bytes.Length - 1)]
                
                # Переписываем файл без BOM
                [System.IO.File]::WriteAllBytes($file.FullName, $cleanedBytes)
                
                $totalFixed++
                Write-Host "   ✅ $($file.Name)" -ForegroundColor Green
            }
        }
        catch {
            Write-Host "   ❌ Ошибка при обработке $($file.Name): $_" -ForegroundColor Red
        }
    }
}

$endTime = Get-Date
$duration = ($endTime - $startTime).TotalSeconds

Write-Host "`n" -NoNewline
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "📊 ИТОГОВЫЙ ОТЧЕТ" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "✅ Исправлено файлов (BOM удален): $totalFixed" -ForegroundColor Green
Write-Host "📝 Всего проверено файлов: $totalChecked" -ForegroundColor White
Write-Host "⏱️  Время выполнения: $([Math]::Round($duration, 2)) сек" -ForegroundColor White
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host ""

if ($totalFixed -eq 0) {
    Write-Host "✨ Все файлы в порядке! BOM не найден." -ForegroundColor Green
} else {
    Write-Host "✨ BOM успешно удален из $totalFixed файлов!" -ForegroundColor Green
}

Write-Host ""
