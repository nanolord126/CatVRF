# Full Project Audit - Completeness Check
# Excludes: vendor, node_modules, build, dist, storage/framework, .git
# Criteria: Files < 60 lines = FAIL

$excludeDirs = @('vendor', 'node_modules', 'build', 'dist', 'storage/framework', '.git', '.github', 'public/build', 'resources/dist', 'bootstrap/cache')

$extensions = @('*.php', '*.blade.php', '*.vue', '*.ts', '*.js')
$excludePatterns = @('*\vendor\*', '*\node_modules\*', '*\build\*', '*\dist\*', '*\storage\framework\*', '*\.git\*')

$results = @{
    pass = @()
    fail = @()
    total = 0
}

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║      FULL PROJECT AUDIT - COMPLETENESS CHECK           ║" -ForegroundColor Cyan
Write-Host "║     (Excluding vendor and working directories)         ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$locations = @(
    'app',
    'config',
    'database',
    'routes',
    'resources/views',
    'resources/js'
)

$counter = 0

foreach ($location in $locations) {
    $path = Join-Path $pwd $location
    if (-not (Test-Path $path)) { continue }
    
    Write-Host "Scanning $location..." -ForegroundColor Yellow
    
    $files = Get-ChildItem -Path $path -Include $extensions -Recurse -ErrorAction SilentlyContinue
    
    foreach ($file in $files) {
        $fullPath = $file.FullName
        
        # Check if file should be excluded
        $shouldExclude = $false
        foreach ($pattern in $excludePatterns) {
            if ($fullPath -like $pattern) {
                $shouldExclude = $true
                break
            }
        }
        
        if ($shouldExclude) { continue }
        
        $lineCount = @(Get-Content $fullPath -ErrorAction SilentlyContinue).Count
        if ($lineCount -eq 0) { $lineCount = 1 }
        
        $results.total++
        $counter++
        
        if ($lineCount -lt 60) {
            $results.fail += @{
                name = $file.Name
                path = $fullPath.Replace($pwd, '')
                lines = $lineCount
            }
            Write-Host "  [FAIL] $($file.Name) - $lineCount lines" -ForegroundColor Red
        } else {
            $results.pass += @{
                name = $file.Name
                lines = $lineCount
            }
        }
        
        if ($counter % 10 -eq 0) {
            Write-Host "    ($counter files scanned)" -ForegroundColor Gray
        }
    }
}

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                    AUDIT RESULTS                       ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$passCount = $results.pass.Count
$failCount = $results.fail.Count

Write-Host "Total Files Scanned: $($results.total)" -ForegroundColor White
Write-Host "✅ PASS (60+ lines): $passCount" -ForegroundColor Green
Write-Host "❌ FAIL (<60 lines): $failCount" -ForegroundColor Red
Write-Host ""

if ($failCount -gt 0) {
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Red
    Write-Host "INCOMPLETE FILES REQUIRING COMPLETION:" -ForegroundColor Red
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Red
    Write-Host ""
    
    $results.fail | Sort-Object { $_.lines } | ForEach-Object {
        $percent = [math]::Round(($_.lines / 60) * 100)
        Write-Host "  ❌ $($_.name)" -ForegroundColor Red
        Write-Host "     Lines: $($_.lines)/60 ($percent%)" -ForegroundColor Yellow
        Write-Host "     Path: $($_.path)" -ForegroundColor Gray
        Write-Host ""
    }
    
    Write-Host ""
    Write-Host "⚠️  WARNING: $failCount files are incomplete." -ForegroundColor Yellow
    Write-Host "   These files must be completed before production deployment!" -ForegroundColor Yellow
} else {
    Write-Host "✅ ALL FILES COMPLETE AND PRODUCTION-READY!" -ForegroundColor Green
}

Write-Host ""

if ($failCount -gt 0) {
    Write-Host "Suggested actions:" -ForegroundColor Yellow
    Write-Host "  1. Review each incomplete file" -ForegroundColor Gray
    Write-Host "  2. Add necessary implementation" -ForegroundColor Gray
    Write-Host "  3. Ensure 60+ lines of code/documentation" -ForegroundColor Gray
    Write-Host "  4. Re-run audit to verify completion" -ForegroundColor Gray
}

Write-Host ""
