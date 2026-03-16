$projectRoot = "C:\opt\kotvrf\CatVRF"
$deleteLog = @()

Write-Host "Deleting app/Domains..." -ForegroundColor Cyan
$domainPath = Join-Path $projectRoot "app\Domains"
if (Test-Path $domainPath) {
    Remove-Item -Path $domainPath -Recurse -Force
    $deleteLog += "Deleted: app/Domains"
    Write-Host "OK: app/Domains removed" -ForegroundColor Green
}

$modulesPath = Join-Path $projectRoot "modules"
$deleteModules = @('Advertising', 'Analytics', 'Apparel', 'Auto', 'BeautyMasters', 'BeautyShop', 'Bonuses', 
                    'Clinic', 'Commissions', 'Communication', 'Construction', 'Delivery', 'Education', 
                    'Electronics', 'Events', 'Food', 'Furniture', 'Geo', 'Hotel', 'Hotels', 'Insurance', 
                    'Inventory', 'RealEstate', 'RealEstateRental', 'RealEstateSales', 'Sports', 'Staff', 
                    'Taxi', 'Tourism')

Write-Host "Deleting modules..." -ForegroundColor Cyan
foreach ($module in $deleteModules) {
    $modulePath = Join-Path $modulesPath $module
    if (Test-Path $modulePath) {
        Remove-Item -Path $modulePath -Recurse -Force
        $deleteLog += "Deleted module: modules/$module"
        Write-Host "  OK: modules/$module" -ForegroundColor Green
    }
}

Write-Host "Deleting .bak and tmp files..." -ForegroundColor Cyan
Get-ChildItem -Path $projectRoot -Recurse -Include "*.bak", "*_backup", "tmp_*" -Force -ErrorAction SilentlyContinue | 
    ForEach-Object {
        Remove-Item -Path $_.FullName -Force
        $deleteLog += "Deleted file: $($_.FullName.Replace($projectRoot, ''))"
    }

Write-Host "`n=== CLEANUP REPORT ===" -ForegroundColor Magenta
Write-Host "Total deleted: $($deleteLog.Count) items" -ForegroundColor Yellow
$deleteLog | ForEach-Object { Write-Host "  - $_" }

$reportPath = Join-Path $projectRoot "CLEANUP_DELETION_LOG.txt"
$deleteLog | Out-File -FilePath $reportPath -Encoding UTF8
Write-Host "`nReport saved: CLEANUP_DELETION_LOG.txt" -ForegroundColor Green
Write-Host "Cleanup completed!" -ForegroundColor Green
