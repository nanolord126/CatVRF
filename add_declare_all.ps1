$resourceFiles = @(
    'HealthChecklistRelationManager.php',
    'PetsRelationManager.php',
    'AttendanceResource.php',
    'EmployeeResource.php',
    'LeaveRequestResource.php',
    'MatchesRelationManager.php',
    'TaxiCarResource.php',
    'TaxiDispatcherConsole.php',
    'TaxiDriverResource.php',
    'TaxiFleetResource.php',
    'PropertyResource.php',
    'B2BContractResource.php',
    'B2BInvoiceResource.php',
    'B2BPartnerResource.php',
    'BeautyProductResource.php',
    'BeautySalonResource.php',
    'BehavioralEventResource.php',
    'BrandResource.php',
    'CampaignResource.php',
    'CategoryResource.php',
    'ClinicResource.php',
    'GymResource.php',
    'HotelBookingResource.php',
    'HotelResource.php',
    'MasterResource.php',
    'MedicalCardResource.php',
    'PayoutResource.php',
    'PayrollRunResource.php',
    'ProductResource.php',
    'PromoCampaignResource.php',
    'RoomResource.php',
    'SalarySlipResource.php',
    'StaffScheduleResource.php',
    'StaffTaskResource.php',
    'StockMovementResource.php',
    'VenueResource.php',
    'WalletResource.php',
    'WishlistResource.php'
)

$count = 0
foreach ($file in $resourceFiles) {
    $paths = Get-ChildItem -Path "app\Filament\Tenant\Resources" -Filter $file -Recurse
    foreach ($path in $paths) {
        $content = Get-Content $path.FullName -Raw
        if ($content -notmatch 'declare\(strict_types=1\)') {
            $newContent = $content -replace '(<\?php)', "<?php`r`n`r`ndeclare(strict_types=1);"
            [System.IO.File]::WriteAllText($path.FullName, $newContent, [System.Text.Encoding]::UTF8)
            $count++
        }
    }
}
Write-Output "Updated $count files"
