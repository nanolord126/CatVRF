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

# UTF-8 encoding WITHOUT BOM
$utf8NoBOM = New-Object System.Text.UTF8Encoding $false

foreach ($path in $allPaths) {
    if (Test-Path $path -PathType Container) {
        # Directory - get all PHP files
        Get-ChildItem -Path $path -Filter "*.php" -Recurse | ForEach-Object {
            Write-Host "Processing (BOM removal): $($_.FullName)"
            
            # Read with detection of current encoding
            $bytes = [System.IO.File]::ReadAllBytes($_.FullName)
            
            # Remove BOM if present (EF BB BF for UTF-8)
            if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
                $bytes = $bytes[3..($bytes.Length - 1)]
            }
            
            # Read as string
            $content = [System.Text.Encoding]::UTF8.GetString($bytes)
            
            # Normalize line endings to CRLF
            $content = $content -replace "`r`n", "`n" -replace "`n", "`r`n"
            
            # Write back with UTF-8 NO BOM
            [System.IO.File]::WriteAllText($_.FullName, $content, $utf8NoBOM)
        }
    } elseif (Test-Path $path -PathType Leaf) {
        # File
        Write-Host "Processing (BOM removal): $path"
        
        $bytes = [System.IO.File]::ReadAllBytes($path)
        
        # Remove BOM if present
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            $bytes = $bytes[3..($bytes.Length - 1)]
        }
        
        $content = [System.Text.Encoding]::UTF8.GetString($bytes)
        $content = $content -replace "`r`n", "`n" -replace "`n", "`r`n"
        
        [System.IO.File]::WriteAllText($path, $content, $utf8NoBOM)
    }
}

Write-Host "All files converted to UTF-8 WITHOUT BOM + CRLF!"
