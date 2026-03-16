$files = Get-ChildItem -Path . -Recurse -Filter "*.php" -ErrorAction SilentlyContinue

$brokenCount = 0
foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    
    # Проверяем, если на одной строке (примерный индикатор)
    if ($content -match "^<\?php[^`n]*namespace" -and $content.Length -gt 500) {
        $relativePath = $file.FullName.Replace((Get-Location).Path + '\', '')
        Write-Host "Fixing: $relativePath" -ForegroundColor Yellow
        
        # Начинаем с чистого лейта
        $fixed = $content -replace "^(<\?php declare[^;]*;)\s+", "`$1`n`n"
        
        # Добавляем переносы после namespace и use
        $fixed = $fixed -replace "namespace\s+([^;]+);", "namespace `$1;`n"
        $fixed = $fixed -replace "use\s+([^;]+);", "use `$1;`n"
        
        # Убираем дублирующиеся final
        $fixed = $fixed -replace "final\s+final", "final"
        
        # Добавляем переносы перед class
        $fixed = $fixed -replace "`n\s*final\s+class", "`n`nfinal class"
        $fixed = $fixed -replace "`n\s*class", "`n`nclass"
        
        # Добавляем переносы в теле класса
        $fixed = $fixed -replace "\}\s+protected\s+", "`n`n    protected "
        $fixed = $fixed -replace "\}\s+public\s+", "`n`n    public "
        $fixed = $fixed -replace "\}\s+private\s+", "`n`n    private "
        
        # Сохраняем исправленный файл
        [System.IO.File]::WriteAllText($file.FullName, $fixed, [System.Text.Encoding]::UTF8)
        Write-Host "  OK" -ForegroundColor Green
        $brokenCount++
    }
}

Write-Host ""
Write-Host "Total broken files fixed: $brokenCount" -ForegroundColor Cyan
