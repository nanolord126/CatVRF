# PowerShell скрипт для аудита полноты проекта
# Все файлы < 60 строк = FAIL

$rootPath = "C:\opt\kotvrf\CatVRF"
$results = @{
    pass = @()
    fail = @()
    categories = @{}
}

# Функция для получения количества строк
function Get-LineCount {
    param([string]$FilePath)
    $content = Get-Content $FilePath -ErrorAction SilentlyContinue
    if ($content -eq $null) { return 0 }
    if ($content -is [array]) { return $content.Count }
    return 1
}

# Функция для определения категории файла
function Get-FileCategory {
    param([string]$FilePath)
    if ($FilePath -like "*\Models\*") { return "Models" }
    if ($FilePath -like "*\Policies\*") { return "Policies" }
    if ($FilePath -like "*\Http\Controllers\*") { return "Controllers" }
    if ($FilePath -like "*\Services\*") { return "Services" }
    if ($FilePath -like "*\Filament\Tenant\Resources\*") { return "Filament Resources" }
    if ($FilePath -like "*\Filament\Tenant\Pages\*") { return "Filament Pages" }
    if ($FilePath -like "*\database\migrations\*") { return "Migrations" }
    if ($FilePath -like "*\database\seeders\*") { return "Seeders" }
    if ($FilePath -like "*\resources\views\*") { return "Views/Blade" }
    return "Other"
}

# Сканируем все PHP файлы
$phpFiles = Get-ChildItem -Path $rootPath -Filter "*.php" -Recurse -ErrorAction SilentlyContinue
$bladeFiles = Get-ChildItem -Path $rootPath -Filter "*.blade.php" -Recurse -ErrorAction SilentlyContinue

$allFiles = @($phpFiles) + @($bladeFiles)

Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "АУДИТ ПОЛНОТЫ ПРОЕКТА - Все файлы < 60 строк = FAIL" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

foreach ($file in $allFiles) {
    $lineCount = Get-LineCount -FilePath $file.FullName
    $category = Get-FileCategory -FilePath $file.FullName
    $relativePath = $file.FullName.Replace($rootPath, "").TrimStart("\")
    
    # Инициализируем категорию если её нет
    if (-not $results.categories.ContainsKey($category)) {
        $results.categories[$category] = @{ pass = 0; fail = 0; files = @() }
    }
    
    if ($lineCount -ge 60) {
        $results.pass += @{ file = $relativePath; lines = $lineCount; category = $category }
        $results.categories[$category].pass++
        Write-Host "✓ PASS: $relativePath ($lineCount lines)" -ForegroundColor Green
    } else {
        $results.fail += @{ file = $relativePath; lines = $lineCount; category = $category }
        $results.categories[$category].fail++
        $results.categories[$category].files += @{ file = $relativePath; lines = $lineCount }
        Write-Host "✗ FAIL: $relativePath ($lineCount lines)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "ИТОГОВЫЙ ОТЧЁТ" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

$totalPass = $results.pass.Count
$totalFail = $results.fail.Count
$totalFiles = $totalPass + $totalFail
$percentPass = if ($totalFiles -gt 0) { [math]::Round(($totalPass / $totalFiles) * 100, 1) } else { 0 }

Write-Host "Всего файлов: $totalFiles" -ForegroundColor White
Write-Host "✓ PASS: $totalPass файлов ($percentPass%)" -ForegroundColor Green
Write-Host "✗ FAIL: $totalFail файлов" -ForegroundColor Red
Write-Host ""

Write-Host "ПО КАТЕГОРИЯМ:" -ForegroundColor Yellow
foreach ($category in $results.categories.Keys | Sort-Object) {
    $cat = $results.categories[$category]
    $catTotal = $cat.pass + $cat.fail
    $catPercent = if ($catTotal -gt 0) { [math]::Round(($cat.pass / $catTotal) * 100, 1) } else { 0 }
    
    Write-Host "  $category`: Pass=$($cat.pass) Fail=$($cat.fail) ($catPercent%)" -ForegroundColor Cyan
    
    # Показываем FAIL файлы этой категории
    if ($cat.files.Count -gt 0) {
        foreach ($failFile in $cat.files) {
            Write-Host "    ✗ $($failFile.file) - $($failFile.lines) lines" -ForegroundColor DarkRed
        }
    }
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

# Сохраняем в JSON
$jsonReport = @{
    timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    total_files = $totalFiles
    pass_count = $totalPass
    fail_count = $totalFail
    pass_percentage = $percentPass
    categories = @{}
    fail_files = @()
}

foreach ($category in $results.categories.Keys) {
    $cat = $results.categories[$category]
    $jsonReport.categories[$category] = @{
        pass = $cat.pass
        fail = $cat.fail
        files = $cat.files
    }
}

foreach ($failFile in $results.fail) {
    $jsonReport.fail_files += @{
        file = $failFile.file
        lines = $failFile.lines
        category = $failFile.category
    }
}

$jsonReport | ConvertTo-Json -Depth 5 | Out-File -FilePath "$rootPath\audit_completeness.json" -Encoding UTF8

Write-Host "✓ Отчёт сохранён в audit_completeness.json" -ForegroundColor Green
