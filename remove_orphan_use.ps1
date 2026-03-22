Set-Location $PSScriptRoot
$fixed = 0
$files = Get-ChildItem -Path "app" -Recurse -Filter "*.php"
foreach ($f in $files) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    $hasUse = $content -match 'use App\\Services\\FraudControlService;'
    $hasInjection = $content -match 'FraudControlService \$\w+'
    if ($hasUse -and -not $hasInjection) {
        # Remove the orphan use line (with CRLF or LF ending)
        $newContent = $content -replace 'use App\\Services\\FraudControlService;\r?\n', ''
        if ($newContent -ne $content) {
            [System.IO.File]::WriteAllText($f.FullName, $newContent, [System.Text.Encoding]::UTF8)
            $fixed++
            Write-Host "Removed orphan use: $($f.Name)"
        }
    }
}
Write-Host ""
Write-Host "=== Итого удалено orphan use: $fixed ==="
