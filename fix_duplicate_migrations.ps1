$tenantPath = "C:\opt\kotvrf\CatVRF\database\migrations\tenant"

$renames = @(
    @{ old = "2026_03_06_100000_create_ai_ml_infrastructure_tables.php"; new = "2026_03_06_100001_create_ai_ml_infrastructure_tables.php" },
    @{ old = "2026_03_06_100000_create_inventory_module_tables.php"; new = "2026_03_06_100002_create_inventory_module_tables.php" },
    @{ old = "2026_03_06_150000_create_payroll_module_tables.php"; new = "2026_03_06_150001_create_payroll_module_tables.php" },
    @{ old = "2026_03_06_300000_create_b2b_supply_tables.php"; new = "2026_03_06_300002_create_b2b_supply_tables.php" },
    @{ old = "2026_03_06_400000_create_hr_exchange_platform_tables.php"; new = "2026_03_06_400001_create_hr_exchange_platform_tables.php" },
    @{ old = "2026_03_06_800000_create_full_taxi_vertical_tables.php"; new = "2026_03_06_800001_create_full_taxi_vertical_tables.php" },
    @{ old = "2026_03_06_900000_create_core_verticals_production_tables.php"; new = "2026_03_06_900001_create_core_verticals_production_tables.php" },
    @{ old = "2026_03_07_000001_create_video_calls_table.php"; new = "2026_03_07_000002_create_video_calls_table.php" }
)

foreach ($rename in $renames) {
    $oldPath = Join-Path $tenantPath $rename.old
    $newPath = Join-Path $tenantPath $rename.new
    
    if (Test-Path $oldPath) {
        Rename-Item -Path $oldPath -NewName $rename.new -Force
        Write-Host "Renamed: $($rename.old) to $($rename.new)"
    }
}
