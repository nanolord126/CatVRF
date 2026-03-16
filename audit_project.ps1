# Full Project Audit - Files < 60 lines = FAIL

$excludeDirs = @('vendor', 'node_modules', 'build', 'dist', 'storage/framework', '.git', '.github', 'public/build')

$extensions = @('*.php', '*.blade.php', '*.vue')
$excludePatterns = @('*\vendor\*', '*\node_modules\*', '*\build\*', '*\dist\*', '*\storage\framework\*', '*\.git\*')

$results = @{
    pass = @()
    fail = @()
    total = 0
}

Write-Host ""
Write-Host "FULL PROJECT AUDIT - COMPLETENESS CHECK" -ForegroundColor Cyan
Write-Host "(Excluding vendor and working directories)" -ForegroundColor Cyan
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
            Write-Host "[FAIL] $($file.Name) - $lineCount lines" -ForegroundColor Red
        } else {
            $results.pass += @{
                name = $file.Name
                lines = $lineCount
            }
        }
        
        if ($counter % 10 -eq 0) {
            Write-Host "($counter files)" -ForegroundColor Gray
        }
    }
}

Write-Host ""
Write-Host "==== AUDIT RESULTS ====" -ForegroundColor Cyan
Write-Host ""

$passCount = $results.pass.Count
$failCount = $results.fail.Count

Write-Host "Total Files: $($results.total)"
Write-Host "PASS (60+ lines): $passCount" -ForegroundColor Green
Write-Host "FAIL (< 60 lines): $failCount" -ForegroundColor Red
Write-Host ""

if ($failCount -gt 0) {
    Write-Host "INCOMPLETE FILES:" -ForegroundColor Red
    Write-Host ""
    
    $results.fail | Sort-Object { $_.lines } | ForEach-Object {
        $percent = [math]::Round(($_.lines / 60) * 100)
        Write-Host "$($_.name) - $($_.lines)/60 lines ($percent%)" -ForegroundColor Red
        Write-Host "  $($_.path)" -ForegroundColor Gray
        Write-Host ""
    }
    
    Write-Host "WARNING: $failCount files are incomplete!" -ForegroundColor Yellow
    Write-Host "These files must be completed before production!" -ForegroundColor Yellow
} else {
    Write-Host "ALL FILES COMPLETE!" -ForegroundColor Green
}

Write-Host ""
