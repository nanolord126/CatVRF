# Скрипт конвертации всех PHP файлов на UTF-8 no BOM + CRLF
# CRITICAL: Правильная конвертация кодировки и окончаний строк

$projectRoot = "c:\opt\kotvrf\CatVRF"
$phpFiles = Get-ChildItem -Path $projectRoot -Filter "*.php" -Recurse -ErrorAction SilentlyContinue

Write-Host "🔍 Найдено PHP файлов: $($phpFiles.Count)" -ForegroundColor Cyan
Write-Host "⏳ Начинаю конвертацию в UTF-8 no BOM + CRLF..." -ForegroundColor Yellow

$converted = 0
$errors = 0
$skipped = 0

foreach ($file in $phpFiles) {
    try {
        # Читаем файл как бинарные данные
        $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
        
        # Проверяем BOM UTF-8 (0xEF 0xBB 0xBF)
        $hasBom = $bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF
        
        # Декодируем
        $encoding = [System.Text.Encoding]::UTF8
        if ($hasBom) {
            # Если есть BOM, пропускаем первые 3 байта
            $text = $encoding.GetString($bytes, 3, $bytes.Length - 3)
        } else {
            $text = $encoding.GetString($bytes)
        }
        
        # Нормализуем окончания строк: сначала LF -> CRLF, потом CRLF -> CRLF (безопасно)
        $text = $text -replace "`r`n", "`n"  # Все CRLF -> LF
        $text = $text -replace "`r", "`n"    # Все CR -> LF
        $text = $text -replace "`n", "`r`n"  # Все LF -> CRLF
        
        # Кодируем в UTF-8 БЕЗ BOM
        $utf8NoBom = New-Object System.Text.UTF8Encoding $false
        $newBytes = $utf8NoBom.GetBytes($text)
        
        # Сравниваем (если файл не изменился, пропускаем запись)
        $oldText = [System.Text.Encoding]::UTF8.GetString($bytes)
        if ($oldText -ne $text -or $hasBom) {
            # Записываем обратно БЕЗ BOM и с CRLF
            [System.IO.File]::WriteAllBytes($file.FullName, $newBytes)
            $converted++
            
            if ($converted % 500 -eq 0) {
                Write-Host "  ✓ Обработано: $converted файлов..." -ForegroundColor Green
            }
        } else {
            $skipped++
        }
    }
    catch {
        Write-Host "  ✗ Ошибка в $($file.Name): $_" -ForegroundColor Red
        $errors++
    }
}

Write-Host ""
Write-Host "✅ Конвертация завершена!" -ForegroundColor Green
Write-Host "   Конвертировано: $converted файлов" -ForegroundColor Cyan
Write-Host "   Пропущено (не требовалось): $skipped файлов" -ForegroundColor Yellow
Write-Host "   Ошибок: $errors файлов" -ForegroundColor $(if ($errors -gt 0) { "Red" } else { "Green" })
Write-Host ""
Write-Host "📊 Всего обработано: $($phpFiles.Count) файлов"
