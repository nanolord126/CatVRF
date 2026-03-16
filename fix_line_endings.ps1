$directories = @(
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\Beauty\Pages',
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\BeautySalonResource\Pages',
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\ClinicResource\Pages',
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\Clinics\Pages',
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\BehavioralEventResource\Pages'
)

$resources = @(
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\BeautySalonResource.php',
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\ClinicResource.php',
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\Clinics\ClinicResource.php',
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\BehavioralEventResource.php',
    'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources\Marketplace\Beauty\BeautySalonResource.php'
)

$allPaths = $directories + $resources

foreach ($path in $allPaths) {
    if (Test-Path $path -PathType Container) {
        # Directory - get all PHP files
        Get-ChildItem -Path $path -Filter "*.php" -Recurse | ForEach-Object {
            Write-Host "Converting: $($_.FullName)"
            $content = [System.IO.File]::ReadAllText($_.FullName, [System.Text.Encoding]::UTF8)
            $contentWithCRLF = $content -replace "`r`n", "`n" -replace "`n", "`r`n"
            [System.IO.File]::WriteAllText($_.FullName, $contentWithCRLF, [System.Text.Encoding]::UTF8)
        }
    } elseif (Test-Path $path -PathType Leaf) {
        # File
        Write-Host "Converting: $path"
        $content = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)
        $contentWithCRLF = $content -replace "`r`n", "`n" -replace "`n", "`r`n"
        [System.IO.File]::WriteAllText($path, $contentWithCRLF, [System.Text.Encoding]::UTF8)
    }
}

Write-Host "Conversion completed!"
