# Полный аудит всех контроллеров и основных файлов
$controllers = Get-ChildItem -Path "app/Http/Controllers" -Filter "*.php" -Recurse -File | Where-Object { $_.Name -notmatch "^Controller\.php$" }
$failed = @()

foreach ($controller in $controllers) {
    $content = Get-Content $controller.FullName -Raw
    $lines = ($content -split "`n").Count
    $methods = ([regex]::Matches($content, 'public\s+function|private\s+function|protected\s+function')).Count
    $hasLogging = $content -match 'Log::|logger\(|->log\('
    $hasErrorHandling = $content -match 'try\s*\{|catch\s*\('
    $emptyMethods = ([regex]::Matches($content, 'function\s+\w+\s*\(\s*\)\s*\{\s*\}')).Count
    $emptyReturns = $content -match 'return\s*;|return\s+null'
    
    $issues = @()
    
    if ($lines -lt 150) { $issues += "FAIL: менее 150 строк ($lines)" }
    if (-not $hasLogging) { $issues += "FAIL: нет логирования" }
    if (-not $hasErrorHandling) { $issues += "FAIL: нет обработки ошибок" }
    if ($methods -lt 4) { $issues += "FAIL: менее 4 методов ($methods)" }
    if ($emptyMethods -gt 0) { $issues += "FAIL: $emptyMethods пустых методов" }
    if ($emptyReturns) { $issues += "FAIL: пустые returns" }
    
    if ($issues.Count -gt 0) {
        $failed += @{
            file = $controller.FullName.Replace((Get-Location).Path + '\', '')
            lines = $lines
            methods = $methods
            issues = $issues -join "; "
        }
    }
}

$failed | ConvertTo-Json | Out-File "FAILED_CONTROLLERS_AUDIT.json" -Encoding UTF8
Write-Host "Контроллеров с проблемами: $($failed.Count)"
$failed | Select-Object @{N='File';E={$_.file}}, @{N='Lines';E={$_.lines}}, @{N='Methods';E={$_.methods}} | Format-Table

