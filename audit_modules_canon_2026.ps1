# AUDIT SCRIPT - CANON 2026 COMPLIANCE CHECK
# Проверка 8 критичных модулей

param(
    [string]$BasePath = "C:\opt\kotvrf\CatVRF"
)

# 8 модулей для аудита
$modules = @(
    @{ name = "Authorization and RBAC"; paths = @("app/Policies", "app/Http/Requests", "app/Services/Auth*") },
    @{ name = "Notifications"; paths = @("app/Notifications", "app/Listeners") },
    @{ name = "Marketing and Promo"; paths = @("app/Services/Promo*", "app/Services/Referral*", "app/Services/Bonus*") },
    @{ name = "Analytics and BigData"; paths = @("app/Services/*Analytics*", "app/Services/*Forecast*", "app/Services/*Report*") },
    @{ name = "HR and Personnel"; paths = @("app/Domains", "app/Services/Staff*") },
    @{ name = "Payroll and Calculations"; paths = @("app/Services/*Payroll*", "app/Services/*Salary*") },
    @{ name = "Couriers and Logistics"; paths = @("app/Domains/Logistics", "app/Services/*Delivery*") },
    @{ name = "Payments and Wallet"; paths = @("app/Services/Payment*", "app/Services/Wallet*") }
)

# Проверка КАНОНА 2026
function Check-Canon2026 {
    param(
        [string]$FilePath,
        [array]$Checks = @("declare_strict_types", "readonly_constructor", "db_transaction", "audit_log", "fraud_check", "rate_limiter", "tenant_scoping", "correlation_id", "error_handling", "no_todo")
    )

    $content = Get-Content -Path $FilePath -Raw -ErrorAction SilentlyContinue
    if (-not $content) { return @{} }

    $results = @{}

    foreach ($check in $Checks) {
        $results[$check] = $false
        
        switch ($check) {
            "declare_strict_types" { $results[$check] = $content -match 'declare\(strict_types=1\)' }
            "readonly_constructor" { $results[$check] = $content -match 'readonly\s+' }
            "db_transaction" { $results[$check] = $content -match 'DB::transaction|transaction\(' }
            "audit_log" { $results[$check] = $content -match "Log::channel\('audit'\)" }
            "fraud_check" { $results[$check] = $content -match 'FraudControlService::check|FraudControlService' }
            "rate_limiter" { $results[$check] = $content -match 'RateLimiter|rate.?limit' }
            "tenant_scoping" { $results[$check] = $content -match 'tenant\(\)|tenant_id|Tenant' }
            "correlation_id" { $results[$check] = $content -match 'correlation_id|correlationId' }
            "error_handling" { $results[$check] = $content -match 'try\s*{|catch\s*\(' }
            "no_todo" { $results[$check] = -not ($content -match '@todo|TODO|FIXME|STUB|XXX|HACK') }
        }
    }

    return $results
}

# Сканирование файлов в модуле
function Get-ModuleFiles {
    param(
        [string]$BasePath,
        [array]$Patterns
    )

    $files = @()

    foreach ($pattern in $Patterns) {
        # Попробуем найти по пути или имени сервиса
        if ($pattern -match '\*') {
            $parts = $pattern -split '\*'
            $searchPath = Join-Path $BasePath ($parts[0])
            
            if (Test-Path $searchPath) {
                $found = Get-ChildItem -Path $searchPath -Filter "$($parts[1] -replace '\*', '')*.php" -Recurse -ErrorAction SilentlyContinue
                $files += $found
            }
        } else {
            $fullPath = Join-Path $BasePath $pattern
            if (Test-Path $fullPath) {
                $found = Get-ChildItem -Path $fullPath -Filter "*.php" -Recurse -ErrorAction SilentlyContinue
                $files += $found
            }
        }
    }

    return $files | Sort-Object FullName -Unique
}

# Классификация файла
function Get-FileType {
    param([string]$FileName, [string]$Content)

    if ($Content -match 'class.*extends.*Request|FormRequest') { return 'Request' }
    if ($Content -match 'class.*extends.*Policy') { return 'Policy' }
    if ($Content -match 'class.*extends.*Job') { return 'Job' }
    if ($Content -match 'class.*extends.*Event') { return 'Event' }
    if ($Content -match 'class.*extends.*Listener') { return 'Listener' }
    if ($Content -match 'class.*extends.*Model') { return 'Model' }
    if ($Content -match 'class.*extends.*Factory') { return 'Factory' }
    if ($Content -match 'class.*extends.*Seeder') { return 'Seeder' }
    if ($Content -match 'class.*extends.*Controller') { return 'Controller' }
    if ($Content -match 'class.*extends.*Mailable|Notification') { return 'Notification' }
    if ($Content -match 'class.*extends.*Resource') { return 'Resource' }
    if ($Content -match 'class.*extends.*Page') { return 'Page' }
    if ($FileName -match 'Service\.php$') { return 'Service' }
    if ($FileName -match 'Repository\.php$') { return 'Repository' }
    if ($FileName -match 'Enum\.php$') { return 'Enum' }
    if ($FileName -match 'Exception\.php$') { return 'Exception' }
    if ($FileName -match 'Middleware\.php$') { return 'Middleware' }
    if ($FileName -match 'Provider\.php$') { return 'Provider' }
    if ($FileName -match 'DTO\.php$') { return 'DTO' }
    
    return 'Other'
}


# Основной аудит
Write-Host "`n=================================================================================" -ForegroundColor Cyan
Write-Host "  AUDIT MODULES ACCORDING TO CANON 2026 (Phase 7)" -ForegroundColor Cyan
Write-Host "=================================================================================" -ForegroundColor Cyan

$totalStats = @{
    TotalFiles = 0
    FilesNeedUpdate = 0
    Modules = @()
}

foreach ($module in $modules) {
    Write-Host "`n>>> MODULE: $($module.name)" -ForegroundColor White
    
    $moduleFiles = Get-ModuleFiles -BasePath $BasePath -Patterns $module.paths
    $moduleStats = @{
        Name = $module.name
        TotalFiles = $moduleFiles.Count
        FilesNeedUpdate = 0
        CompliantFiles = 0
        Files = @()
    }

    if ($moduleFiles.Count -eq 0) {
        Write-Host "    WARNING: No files found" -ForegroundColor Yellow
        $totalStats.Modules += $moduleStats
        continue
    }

    Write-Host "    Found: $($moduleFiles.Count) files" -ForegroundColor Gray

    $fileCount = 0
    foreach ($file in $moduleFiles | Select-Object -First 20) {
        $content = Get-Content -Path $file.FullName -Raw -ErrorAction SilentlyContinue
        if (-not $content) { continue }

        $fileType = Get-FileType -FileName $file.Name -Content $content
        $canonChecks = Check-Canon2026 -FilePath $file.FullName

        $passedChecks = $canonChecks.Values | Where-Object { $_ } | Measure-Object | Select-Object -ExpandProperty Count
        $issues = 10 - $passedChecks

        $fileInfo = @{
            Path = $file.FullName -replace [regex]::Escape($BasePath), "."
            Name = $file.Name
            Type = $fileType
            Issues = $issues
            PassedChecks = $passedChecks
            Checks = $canonChecks
        }

        $moduleStats.Files += $fileInfo
        
        if ($issues -gt 3) {
            $moduleStats.FilesNeedUpdate++
        } elseif ($issues -eq 0) {
            $moduleStats.CompliantFiles++
        }

        # Вывод кратко
        $status = if ($issues -eq 0) { "OK" } elseif ($issues -lt 3) { "WARN" } else { "FAIL" }
        Write-Host "      [$status] $($file.Name) [$fileType] Issues: $issues" -ForegroundColor $(if ($issues -gt 3) { 'Red' } else { 'Yellow' })

        $fileCount++
        if ($fileCount -ge 20) { break }
    }

    $totalStats.TotalFiles += $moduleStats.TotalFiles
    $totalStats.FilesNeedUpdate += $moduleStats.FilesNeedUpdate
    $totalStats.Modules += $moduleStats
}

# ИТОГОВЫЙ ОТЧЕТ
Write-Host "`n=================================================================================" -ForegroundColor Cyan
Write-Host "  FINAL REPORT" -ForegroundColor Cyan
Write-Host "=================================================================================" -ForegroundColor Cyan

Write-Host "`nGeneral Statistics:" -ForegroundColor White
Write-Host "  Total files: $($totalStats.TotalFiles)" -ForegroundColor Gray
Write-Host "  Files need update: $($totalStats.FilesNeedUpdate)" -ForegroundColor Red
$compPercent = if ($totalStats.TotalFiles -gt 0) { [math]::Round((($totalStats.TotalFiles - $totalStats.FilesNeedUpdate) / $totalStats.TotalFiles) * 100) } else { 0 }
Write-Host "  Compliance percentage: $compPercent%" -ForegroundColor Green

Write-Host "`nBy Module:" -ForegroundColor White
$totalStats.Modules | ForEach-Object {
    if ($_.TotalFiles -gt 0) {
        $compliance = [math]::Round((($_.TotalFiles - $_.FilesNeedUpdate) / $_.TotalFiles) * 100)
        $compColor = if ($compliance -ge 80) { 'Green' } else { 'Yellow' }
        Write-Host "  - $($_.Name): $($_.TotalFiles) files, need update: $($_.FilesNeedUpdate), compliance: $compliance%" -ForegroundColor $compColor
    }
}

# Сохранение результатов
$timestamp = Get-Date -Format 'yyyy_MM_dd_HHmmss'
$reportPath = Join-Path $BasePath "AUDIT_CANON_2026_REPORT_$timestamp.json"
$totalStats | ConvertTo-Json -Depth 10 | Out-File -FilePath $reportPath -Encoding UTF8
Write-Host "`nReport saved to: $reportPath" -ForegroundColor Green

Write-Host "`n=================================================================================" -ForegroundColor Cyan
