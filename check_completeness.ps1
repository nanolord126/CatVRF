# Audit script for project completeness
$rootPath = "C:\opt\kotvrf\CatVRF"
$results = @{ pass = @(); fail = @(); categories = @{} }

function Get-LineCount {
    param([string]$FilePath)
    $content = Get-Content $FilePath -ErrorAction SilentlyContinue
    if ($null -eq $content) { return 0 }
    if ($content -is [array]) { return $content.Count }
    return 1
}

function Get-FileCategory {
    param([string]$FilePath)
    if ($FilePath -like "*\Models\*") { return "Models" }
    if ($FilePath -like "*\Policies\*") { return "Policies" }
    if ($FilePath -like "*\Http\Controllers\*") { return "Controllers" }
    if ($FilePath -like "*\Services\*") { return "Services" }
    if ($FilePath -like "*\Filament\Tenant\Resources\*") { return "Resources" }
    if ($FilePath -like "*\Filament\Tenant\Pages\*") { return "Pages" }
    if ($FilePath -like "*\database\migrations\*") { return "Migrations" }
    if ($FilePath -like "*\database\seeders\*") { return "Seeders" }
    if ($FilePath -like "*\resources\views\*") { return "Views" }
    return "Other"
}

Write-Host "==== PROJECT AUDIT ==== COMPLETENESS CHECK (min 60 lines) ====" -ForegroundColor Cyan
Write-Host ""

$allFiles = Get-ChildItem -Path $rootPath -Filter "*.php" -Recurse -ErrorAction SilentlyContinue
$allFiles += Get-ChildItem -Path $rootPath -Filter "*.blade.php" -Recurse -ErrorAction SilentlyContinue

foreach ($file in $allFiles) {
    $lineCount = Get-LineCount -FilePath $file.FullName
    $category = Get-FileCategory -FilePath $file.FullName
    $relativePath = $file.FullName.Replace($rootPath, "").TrimStart("\")
    
    if (-not $results.categories.ContainsKey($category)) {
        $results.categories[$category] = @{ pass = 0; fail = 0; files = @() }
    }
    
    if ($lineCount -ge 60) {
        $results.pass += @{ file = $relativePath; lines = $lineCount; category = $category }
        $results.categories[$category].pass++
    } else {
        $results.fail += @{ file = $relativePath; lines = $lineCount; category = $category }
        $results.categories[$category].fail++
        $results.categories[$category].files += @{ file = $relativePath; lines = $lineCount }
        Write-Host "FAIL: $relativePath ($lineCount lines)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "======= SUMMARY =======" -ForegroundColor Yellow
$totalPass = $results.pass.Count
$totalFail = $results.fail.Count
$totalFiles = $totalPass + $totalFail
$percent = if ($totalFiles -gt 0) { [math]::Round(($totalPass / $totalFiles) * 100, 1) } else { 0 }

Write-Host "Total: $totalFiles files | PASS: $totalPass | FAIL: $totalFail ($percent% complete)" -ForegroundColor White
Write-Host ""
Write-Host "BY CATEGORY:" -ForegroundColor Cyan

foreach ($cat in $results.categories.Keys | Sort-Object) {
    $c = $results.categories[$cat]
    $ct = $c.pass + $c.fail
    $cp = if ($ct -gt 0) { [math]::Round(($c.pass / $ct) * 100, 1) } else { 0 }
    Write-Host "  $cat : Pass=$($c.pass) Fail=$($c.fail) ($cp%)" -ForegroundColor Cyan
    if ($c.fail -gt 0) {
        foreach ($f in $c.files) {
            Write-Host "    - $($f.file) [$($f.lines) lines]" -ForegroundColor DarkRed
        }
    }
}

# Save JSON report
$json = @{
    timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    total = $totalFiles
    pass = $totalPass
    fail = $totalFail
    percent_complete = $percent
    categories = @{}
    fail_files = @()
}

foreach ($cat in $results.categories.Keys) {
    $c = $results.categories[$cat]
    $json.categories[$cat] = @{ pass = $c.pass; fail = $c.fail }
}

foreach ($f in $results.fail) {
    $json.fail_files += @{ file = $f.file; lines = $f.lines; category = $f.category }
}

$json | ConvertTo-Json -Depth 10 | Out-File -FilePath "$rootPath\AUDIT_REPORT.json" -Encoding UTF8
Write-Host ""
Write-Host "Report saved to AUDIT_REPORT.json" -ForegroundColor Green
